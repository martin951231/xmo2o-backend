<?php
/**
 * 团购
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/6/15 11:24
 */

namespace app\group\model\service;

use app\appoint\model\service\goods\AppointService;
use app\common\model\db\Area;
use app\common\model\db\Config;
use app\common\model\db\StockWarn;
use app\common\model\db\WarnList;
use app\common\model\service\AreaService as AreaService;
use app\common\model\service\GoodsScoreService;
use app\common\model\service\order\ReplyService;
use app\common\model\service\percent_rate\PercentRateService;
use app\common\model\service\UserLevelService;
use app\foodshop\model\service\package\FoodshopGoodsPackageDetailService;
use app\foodshop\model\service\package\FoodshopGoodsPackageService;
use app\group\model\db\Group;
use app\group\model\db\GroupBookingAppointCombine;
use app\group\model\db\GroupBookingAppointRule;
use app\group\model\db\GroupBookingAppointRuleCombine;
use app\group\model\db\GroupBookingAppointRuleDetail;
use app\group\model\db\GroupRedemptionCode;
use app\group\model\db\GroupRenovationGoodsCustom;
use app\group\model\db\GroupRenovationGoodsCustomTop;
use app\group\model\db\GroupStore;
use app\group\model\service\appoint\GroupAppointService;
use app\group\model\service\group_combine\GroupCombineActivityGoodsService;
use app\group\model\service\order\GroupOrderService;
use app\group\model\service\order\GroupStartService;
use app\hotel\model\service\TradeHotelCategoryService;
use app\mall\model\service\ExpressTemplateService as ServiceExpressTemplateService;
use app\merchant\model\db\MerchantStore;
use app\merchant\model\service\delivery\ExpressTemplateService;
use app\merchant\model\service\CustomerServiceService;
use app\merchant\model\service\MerchantService;
use app\merchant\model\service\MerchantStoreService;
use app\merchant\model\service\MerchantUserRelationService;
use app\merchant\model\service\store\MerchantStoreKefuService;
use app\store_marketing\model\db\StoreMarketingPersonSetprice;
use app\store_marketing\model\db\StoreMarketingPersonStore;
use app\store_marketing\model\db\StoreMarketingShareLog;
use app\store_marketing\model\db\StoreMarketingRatio;
use app\store_marketing\model\db\StoreMarketingPerson;
use app\warn\model\db\WarnNotice;
use redis\Redis;
use think\Exception;
use think\facade\Db;
use think\helper\Arr;

class GroupService
{
    public $groupModel = null;
    public $studyBasicArr = null;
    public $groupCateNameArr = null;
    public $statusArr = null;

    public function __construct()
    {
        $this->groupModel = new Group();
        $this->studyBasicArr = [
            1 => L_('零基础'),
            2 => L_('初级'),
            3 => L_('中级 '),
            4 => L_('高级'),
        ];
        $this->groupCateNameArr = [
            'normal' => L_('团购商品'),
            'booking_appoint' => L_('场次预约'),
            'cashing' => L_('代金券'),
            'course_appoint' => L_('课程预约'),
        ];
        $this->statusArr = [
            '0' => L_('关闭'),
            '1' => L_('正常'),
            '2' => L_('审核中'),
            '4' => L_('已结束'),
        ];
    }
    /**
     * @param $where
     * @param $field
     * @return mixed
     *团购店铺查询
     */
    public function getStoreGroupList($where, $field){
        $groupList = array();
        $list=(new GroupStore())->getStoreGroupList($where, $field);
        if (!empty($list)) {
            foreach ($list as $key => $val) {
                if($val['end_time'] >= time() || $val['group_cate'] == 'booking_appoint'){
                    $groupList[] = $val;
                }
            }
        }
        return $groupList;
    }

    /**
     * @param $where
     * @param $field
     * @return mixed
     *团购店铺更新
     */
    public function getStoreUpdate($wheres, $datas){
        return (new GroupStore())->updateThis($wheres, $datas);
    }
    /**
     * 团购新版前端地址
     */
    public function getUrl($param = [])
    {
        $type = $param['type'] ?? 'index'; // index：首页 find：发现页 channel：频道页
        if ($type == 'index') {
            $url = get_base_url('pages/group/index/home?currentPage=index');
        } elseif ($type == 'find') {
            $url = get_base_url('pages/group/index/home?currentPage=find');
        } elseif ($type == 'channel') {
            $cat_id = $param['cat_id'] ?? '0';
            $group_category = (new GroupCategoryService())->getOne($where = ['cat_id' => $cat_id]);
            $cat_name = empty($group_category) ? '' : $group_category['cat_name'];
            $url = get_base_url('pages/group/index/catPage?cat_fid=' . $cat_id . '&cat_name=' . $cat_name . '&user_long=117.233443&user_lat=31.826578');
        }
        $returnArr = [];
        $returnArr['url'] = $url;
        return $returnArr;
    }

    /**
     * 团购模块全新改版 频道页团购分类商品列表
     */
    public function getChannelGroupList($param = [], $type = 0, $group_by_store = '')
    {
        $cat_fid = $param['cat_fid'] ?? 0; //父分类ID
        $cat_id = $param['cat_id'] ?? 0; //子分类ID
        $city_id = $param['now_city'] ?? 0;//城市id
        $area_id = $param['area_id'] ?? 0;// 区域ID
        $user_long = $param['user_long'] ?? 0.00;
        $user_lat = $param['user_lat'] ?? 0.00;
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 10;
        $sort = $param['sort'] ?? 'defaults';

        $params['sort_id'] = $cat_id;
        $params['cat_fid'] = $cat_fid;

        
        $group_activity_recommendations = cfg('group_activity_recommendations');
        if($group_activity_recommendations) {
            $params['sort'] = $params['sort']??'';
            $params['custom_id'] = $params['custom_id']??0;
            $params['user_long'] = $user_long;
            $params['user_lat'] = $user_lat;
            $params['page'] = $page;
            $params['pageSize'] = $pageSize;
            $params['from_category'] = 1;
            $params['new_nearby'] = $param['new_nearby']??'';
            $params['score'] = $param['score']??'';
            $params['city_id'] = $city_id;
            $params['area_id'] = $area_id;
            $list = (new GroupRenovationCustomService())->getCustomGroupList($params);
            return $list;
        }

        $nowArea = (new \app\common\model\db\Area())->where('area_id', $area_id)->find();
        if ($nowArea) {
            if ($nowArea->area_type == 3) {
                $params['area_id'] = $area_id;
            } else if ($nowArea->area_type == 4) {
                $params['circle_id'] = $area_id;
            }
        }

        if (!empty($sort)) {
            $params['sort'] = $sort;
        }

        if ($city_id > 0) {
            $params['city_id'] = $city_id;
        }

        $params['status'] = 1;
        $params['no_booking_appoint'] = 1;
        $groupGoods = $this->getGroupGoodsList($params, [], [], $type,$group_by_store);

        $group_list = $groupGoods['list'];
        $store_count = 0;
        if (!empty($group_list)) {
            $group = [];
            foreach ($group_list as $_group) {
                $group[$_group['mer_id']][] = $_group;
            }
//            $mer_id_arr = array_unique(array_column($group_list, 'mer_id'));
            $store_id_arr = array_unique(array_column($group_list, 'store_id'));
            $condition = [
//                ['mer_id', 'in', $mer_id_arr],
                ['store_id', 'in', $store_id_arr],
                ['status', '=', 1],
            ];
            $store_count = (new MerchantStoreService())->getCount($condition);
//            $mer_id_str = implode(',', $mer_id_arr);
            $store_id_str = implode(',', $store_id_arr);
//            $where = " s.`status` = 1 AND s.mer_id in ($mer_id_str) ";
            $where = " s.`status` = 1 AND s.store_id in ($store_id_str) ";

            $order = 's.sort DESC,s.store_id DESC';
            if ($user_lat > 0 && $user_long > 0) {
                $order = 'distance asc';
            }
            $store_list = (new MerchantStoreService())->getUserDistance($where, $user_long, $user_lat, $order, $page, $pageSize);
            $list = [];
            foreach ($store_list as $store) {
                $arr['logo'] = $store['logo'] ? thumb_img($store['logo'], 200, 200, 'fill') : '';
                if (!$arr['logo'] && $store['pic_info']) {
                    $arr['logo'] = thumb(explode(';', $store['pic_info'])[0], 200, 200, 'fill');
                }
                $arr['store_id'] =$store['store_id'];
                $arr['name'] = $store['name'];
                $arr['score'] = !empty($store['score']) ? get_format_number($store['score']) : '5.0';
                $arr['permoney'] = !empty($store['permoney']) ? $store['permoney'] : 0.0;
                $arr['cat_name'] = !empty($store['cat_name']) ? $store['cat_name'] : '';
                $arr['address'] = $store['adress'];
                $distance = isset($store['distance']) ? sprintf("%.2f", $store['distance']) : '';
                $arr['distance'] = $distance <= 0 ? '' : $distance . 'km';
                $arr['url'] =cfg('site_url') . '/packapp/platn/pages/store/v1/home/index?store_id='.$store['store_id'];
                $list1=(new GroupStore())->getStoreGroupList([['a.store_id','=',$store['store_id']],['g.group_cate','not in',['booking_appoint','course_appoint','cashing']],['g.status','=',1]],'g.s_name,g.group_id,g.name,g.old_price,g.price');
                if (!empty($list1)) {
                    $group_list1 = [];
                    foreach ($list1 as $key => $val) {
                        if ($key < 2) {
                            $a['group_id'] = $val['group_id'];
                            $a['name'] = $val['s_name'];
                            $a['old_price'] = get_format_number($val['old_price']);
                            $a['price'] = get_format_number($val['price']);
                            $group_list1[] = $a;
                        }
                    }
                    $arr['group_list'] = $group_list1;
                } else {
                    $arr['group_list'] = [];
                }
                $list[] = $arr;
            }
        } else {
            $list = [];
        }
        $returnArr = [];
        $returnArr['list'] = $list;
        $returnArr['count'] = $store_count;
        $returnArr['page'] = ceil($store_count / $pageSize);
        return $returnArr;

        //获取
    }

    /**首页 发现页自定义分类 团购商品列表
     * @param array $param
     * @return array
     */
    public function getCustomGroupList($param = [])
    {
        $custom_id = $param['custom_id'] ?? 0;
        $city_id = $param['now_city'] ?? 0;//城市id
        $area_id = $param['now_area'] ?? 0;//区县id
        $user_long = $param['user_long'] ?? '23.146436';
        $user_lat = $param['user_lat'] ?? '113.323568';
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 10;
        $group_activity_recommendations = cfg('group_activity_recommendations');
        $params = [];
        $params['custom_id'] = $custom_id;
        $params['user_long'] = $user_long;
        $params['user_lat'] = $user_lat;
        $params['city_id'] = $city_id;
        $params['page'] = $page;
        $params['pageSize'] = $pageSize;
        $params['have_group'] = 1;
        $params['no_booking_appoint'] = 1;
        $params['sort'] = $params['sort']??'';
        $params['sort_value'] = $params['sort_value']??'';
        $params['new_nearby'] = $param['new_nearby']??'';
        $params['score'] = $param['score']??'';
        if($group_activity_recommendations) {
            $params['area_id'] = $area_id;
            $list = (new GroupRenovationCustomService())->getCustomGroupList($params);
        }else {
            $list = (new GroupRenovationCustomStoreSortService())->getCustomGroupList($params);
        }
        return $list;
    }

    /**首页 发现页自定义分类  0:首页 1:发现页
     * @param array $param
     * @return array
     */
    public function getCustomList($param = [])
    {
        $cat_id = $param['cat_id'] ?? 0;
        if(cfg('group_activity_recommendations')){
            $res = (new GroupRenovationGoodsCustom())->order('sort desc,custom_id asc')->select()->toArray();
        }else {
            $res = (new GroupRenovationCustomService())->getSome($where = ['cat_id' => $cat_id], true, 'sort desc');
        }
        $custom_list = [];
        if (!empty($res)) {
            foreach ($res as $value) {
                $arr['custom_id'] = $value['custom_id'];
                $arr['title'] = $value['title'];
                $arr['sub_title'] = $value['sub_title']??'';
                $arr['img'] = !empty($value['img'])?replace_file_domain($value['img']):'';
                $custom_list[] = $arr;
            }
        }
        $returnArr = [];
        $returnArr['list'] = $custom_list;
        return $returnArr;
    }

    /**
     * 获取 首页：优选商品 频道页：精选商品列表
     */
    public function getSelectGoods($param = [])
    {
        $cat_id = $param['cat_id'] ?? 0;//分类ID 0:首页 其它:频道页
        $city_id = $param['now_city'] ?? 0;//城市id
        $sort_id = $param['sort_id'] ?? 0;//列表页分类ID
        $area_id = $param['area_id'] ?? 0;// 附近 区域ID
        $keyword = $param['keyword'] ?? '';//搜索商品
        $sort = $param['sort'] ?? '';// 智能排序
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 10;

        $config_info = (new GroupConfigRenovationService())->getOne($where = ['cat_id' => $cat_id, 'status' => 1, 'type' => 1]);
        if (!empty($config_info)) {
            $param = [];
            $param['config_id'] = $config_info['config_id'];
            $param['sort_id'] = $sort_id;
            $param['city_id'] = $city_id;
            $param['page'] = $page;
            $param['limit'] = $pageSize;

            $count = (new GroupConfigRenovationGoodsService())->getSelectCount($param);
            $list = (new GroupConfigRenovationGoodsService())->getSelectList($param);

            $group_list = [];
            $groupImage = new GroupImageService();
            foreach ($list as $value) {
                $arr['group_id'] = $value['group_id'];
                $tmp_pic_arr = explode(';', $value['pic']);
                $pic = $groupImage->getImageByPath($tmp_pic_arr[0], 's');
                $arr['pic'] = thumb_img($pic, 320, 205, 'fill');
                $arr['name'] = $value['name'];
                $arr['merchant_name'] = $value['merchant_name'];
                $arr['old_price'] = get_format_number($value['old_price']);
                $arr['price'] = get_format_number($value['price']);
                $arr['sale_count'] = $value['sale_count']+$value['virtual_num'];
                $arr['url'] = cfg('site_url') . '/wap.php?g=Wap&c=Groupnew&a=detail&group_id=' . $value['group_id'] . '&page_num=1';
                //消费一元获得积分
                $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($arr['price'],'group',false,$value['score_percent']);
                $arr['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
                $arr['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
                $group_list[] = $arr;
            }
        } else {
            $count = 0;
            $group_list = [];
        }
        $returnArr = [];
        $returnArr['count'] = $count;
        $returnArr['page'] = ceil($count / $pageSize);
        $returnArr['list'] = $group_list;
        return $returnArr;
    }

    /**
     * 获取 首页：超值组合 频道页：超值联盟列表
     */
    public function getCombineGoods($param = [])
    {
        $cat_id = $param['cat_id'] ?? 0;//分类ID 0:首页 其它:频道页
        $sort_id = $param['sort_id'] ?? 0;//列表页分类ID
        $area_id = $param['area_id'] ?? 0;// 附近 区域ID
        $keyword = $param['keyword'] ?? '';//搜索商品
        $sort = $param['sort'] ?? '';// 智能排序
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 10;

        $config_info = (new GroupConfigRenovationService())->getOne($where = ['cat_id' => $cat_id, 'status' => 1, 'type' => 2]);
        if (!empty($config_info)) {
            $param = [];
            $param['config_id'] = $config_info['config_id'];
            $param['sort_id'] = $sort_id;
            $param['page'] = $page;
            $param['limit'] = $pageSize;

            $count = (new GroupRenovationCombineGoodsService())->getCombineCount($param);
            $list = (new GroupRenovationCombineGoodsService())->getCombineList($param);
            $combine_id_arr = array_column($list, 'combine_id');
            $count_list = (new GroupCombineActivityGoodsService())->getCombineCount(['combine_id' => $combine_id_arr]);
            $count_list = array_column($count_list, NULL, 'combine_id');
            $combine_list = [];
            foreach ($list as $value) {
                $arr['combine_id'] = $value['combine_id'];
                $banner_img = $value['banner_img'] ? replace_file_domain($value['banner_img']) : '';
                $arr['banner_img'] = $banner_img ? thumb_img($banner_img, 155, 155, 'fill') : '';
                $arr['title'] = $value['title'];
                $arr['old_price'] = get_format_number($value['old_price']);
                $arr['price'] = get_format_number($value['price']);
                $arr['group_count'] = isset($count_list[$value['combine_id']]['nums']) ? $count_list[$value['combine_id']]['nums'] : 0;
                $arr['can_use_count'] = $value['can_use_count'];
                $arr['sell_count'] = $value['sell_count'];
                $arr['url'] = cfg('site_url') . '/packapp/plat/pages/group/groupCombineDetail?combine_id=' . $value['combine_id'];
                $combine_list[] = $arr;
            }
        } else {
            $count = 0;
            $combine_list = [];
        }
        $returnArr = [];
        $returnArr['count'] = $count;
        $returnArr['page'] = ceil($count / $pageSize);
        $returnArr['list'] = $combine_list;
        return $returnArr;
    }

    /**
     * 获取 特价拼团列表
     */
    public function getDiscountGoods($param = [])
    {
        $cat_id = $param['cat_id'] ?? 0;//分类ID 0:首页 其它:频道页
        $city_id = $param['now_city'] ?? 0;//城市ID
        $area_id = $param['area_id'] ?? 0;// 附近 区域ID
        $keyword = $param['keyword'] ?? '';//搜索商品
        $sort = $param['sort'] ?? 'sale';// 智能排序
        $user_long = $param['user_long'] ?? '23.146436';
        $user_lat = $param['user_lat'] ?? '113.323568';
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 10;


        $params = [];
        $params['sort_id'] = $cat_id;
        $params['city_id'] = $city_id;
        $params['page'] = $page;
        $params['pageSize'] = $pageSize;
        $params['sort'] = $sort;
        $params['status'] = 1;
        $params['pin_num_type'] = 1;


        $groupGoods = $this->getGroupGoodsList($params);
        $group_list = $groupGoods['list'];
        $count = $groupGoods['count'];
        if (!empty($group_list)) {
            $groupCategory = (new GroupCategoryService())->getSome($where = [['cat_id', '>', 0]], 'cat_id,cat_name');
            $groupCategoryList = array_column($groupCategory, NULL, 'cat_id');
            $mer_id_arr = array_unique(array_column($group_list, 'mer_id'));
            $mer_id_str = implode(',', $mer_id_arr);
            $where = " s.`status` = 1 AND s.mer_id in ($mer_id_str) ";
            $store_list = (new MerchantStoreService())->getUserDistance($where, $user_long, $user_lat);
            $store_data = [];
            foreach ($store_list as &$store) {
                $store['distance'] = sprintf("%.2f", $store['distance']);
                $store_data[$store['mer_id']][] = $store;
            }

            foreach ($store_data as $key => $data) {
                $sort_data = sortArrayAsc($data, 'distance');
                $store_list[$key] = $sort_data;
            }

            $discountList = [];
            $groupImage = new GroupImageService();
            foreach ($group_list as $_discount) {
                $discount['group_id'] = $_discount['group_id'];
                $tmp_pic_arr = explode(';', $_discount['pic']);
                $pic = $groupImage->getImageByPath($tmp_pic_arr[0], 's');
                $discount['pic'] = $pic ? thumb_img($pic, 100, 100, 'fill') : '';
                $discount['name'] = $_discount['name'];
                $discount['store_name'] = $store_list[$_discount['mer_id']][0]['name'];
                $store_id = $store_list[$_discount['mer_id']][0]['store_id'];
                $discount['store_id'] = $store_id;
                $discount['cat_id'] = $_discount['cat_id'];
                $discount['cat_name'] = isset($groupCategoryList[$_discount['cat_id']]) ? $groupCategoryList[$_discount['cat_id']]['cat_name'] : '';
                $discount['old_price'] = get_format_number($_discount['old_price']);
                $discount['price'] = get_format_number($_discount['price']);
                $discount['pin_num'] = $_discount['pin_num'];
                $distance = isset($store_list[$_discount['mer_id']][0]['distance']) ? sprintf("%.2f", $store_list[$_discount['mer_id']][0]['distance'] / 1000) : '';
                $discount['distance'] = $distance <= 0 ? '' : $distance . 'km';
                $discount['url'] = cfg('site_url') . '/wap.php?g=Wap&c=Groupnew&a=detail&group_id=' . $_discount['group_id'] . '&page_num=1';
                $discountList[] = $discount;
            }
        } else {
            $discountList = [];
        }
        $returnArr = [];
        $returnArr['list'] = $discountList;
        $returnArr['count'] = $count;
        $returnArr['page'] = ceil($count / $pageSize);
        return $returnArr;
    }

    /**
     * 获取团购模块全新改版 发现页
     */
    public function getSearchList($param = [])
    {
        $cat_id = $param['cat_id'] ?? 0;
        $city_id = $param['now_city'] ?? 0;//城市id
        $user_long = $param['user_long'] ?? '';
        $user_lat = $param['user_lat'] ?? '';

        $returnArr = [];
        // 特色轮播图
        $cat_key = 'wap_group_search_top';
        $banner_list = $this->getAdverList(0, $cat_key, $city_id);

        $returnArr['banner_list'] = $banner_list;

        // 广告
        $cat_key = 'wap_group_search_adver';
        $ad_list = $this->getAdverList(0, $cat_key, $city_id);
        $returnArr['ad_list'] = $ad_list;

        // TOP点打卡
        $store_list = (new MerchantStoreService())->getStoresTop($where = " s.`status` = 1 AND s.have_group = 1 AND mm.status = 1 AND mm.city_id = {$city_id} ", $user_long, $user_lat);
        foreach ($store_list as &$store) {
            $store['logo'] = $store['logo'] ? thumb_img($store['logo'], 200, 200, 'fill') : '';
            if (!$store['logo'] && $store['pic_info']) {
                $store['logo'] = thumb(explode(';', $store['pic_info'])[0], 200, 200, 'fill');
            }

            $store['score'] = empty($store['score']) ? '5.0' : get_format_number($store['score']);
            $distance = isset($store['distance']) ? sprintf("%.2f", $store['distance']) : '';
            $store['distance'] = $distance == 0.00 ? '' : $distance . 'm';
            $store['url'] = cfg('site_url') . '/packapp/plat/pages/store/homePage?store_id=' . $store['store_id'];
        }
        $list[] = [
            'type' => 1,
            'title' => '附近好店',
            'list' => $store_list
        ];

        // 发现页自定义分类
        $custom_category = (new GroupRenovationCustomService())->getSome($where = ['cat_id' => 1], true, 'sort desc');;
        if (!empty($custom_category)) {
            foreach ($custom_category as $value) {
                $custom = [];
                $custom['type'] = 6;
                $custom['title'] = $value['title'];
                $where = ['custom_id' => $value['custom_id'], 'city_id' => $city_id, 'is_not_cashing' => 1];
                $group_list = (new GroupRenovationCustomGroupSortService())->getList($where)['list'];
                $group = [];
                if (!empty($group_list)) {
                    $groupImage = new GroupImageService();
                    foreach ($group_list as $val) {
                        $v['custom_id'] = $value['custom_id'];
                        $v['group_id'] = $val['group_id'];
                        $v['name'] = $val['group_name'];
                        $v['old_price'] = get_format_number($val['old_price']);
                        $v['price'] = get_format_number($val['price']);
                        $tmp_pic_arr = explode(';', $val['pic']);
                        $pic = $groupImage->getImageByPath($tmp_pic_arr[0], 's');
                        $v['pic'] = $pic ? thumb_img($pic, 155, 155, 'fill') : '';
                        $v['sale_count'] = $val['sale_count'];
                        $v['url'] = cfg('site_url') . '/wap.php?g=Wap&c=Groupnew&a=detail&group_id=' . $val['group_id'] . '&page_num=1';
                        $v['buy_can_get_score'] = $val['buy_can_get_score'];
                        $v['buy_can_get_score_txt'] = $val['buy_can_get_score_txt'];
                        $group[] = $v;
                    }
                }
                $custom['list'] = $group;
                $list[] = $custom;
            }
        }

        $returnArr['list'] = $list;
        return $returnArr;
    }

    /**团购模块全新改版 发现页团购分类列表
     * @param array $param
     * @return array
     * @throws \think\Exception
     */
    public function getSearchGroupList($param = [])
    {
        $params = [];
        $params['custom_id'] = $param['custom_id'] ?? 0;
        $params['city_id'] = $param['now_city'] ?? 0;//城市id
        $params['cat_id'] = $param['cat_id'] ?? 0;// 团购分类ID
        $params['page'] = $param['page'] ?? 0;
        $params['pageSize'] = $param['pageSize'] ?? 10;
        $params['is_not_cashing'] = 1;
        $params['no_booking_appoint'] = 1;
        $params['sort'] = $param['sort'] ?? '';// 排序 智能排序：defaults 评价排序：score

        $GroupGoods = (new GroupRenovationCustomGroupSortService())->getList($params);

        $list = $GroupGoods['list'];
        $count = $GroupGoods['count'];
        $group_list = [];
        if (!empty($list)) {
            foreach ($list as $value) {
                $arr['group_id'] = $value['group_id'];
                $arr['name'] = $value['group_name'];
                $arr['old_price'] = get_format_number($value['old_price']);
                $arr['price'] = get_format_number($value['price']);
                $arr['pic'] = $value['pic'] ? thumb_img($value['pic'], 85, 85, 'fill') : '';
                $arr['sale_count'] = $value['sale_count'];
                $arr['url'] = cfg('site_url') . '/wap.php?g=Wap&c=Groupnew&a=detail&group_id=' . $value['group_id'] . '&page_num=1';
                $group_list[] = $arr;
            }
        }


        $returnArr = [];
        $returnArr['list'] = $group_list;
        $returnArr['count'] = $count;
        $returnArr['page'] = ceil($count / $params['pageSize']);
        return $returnArr;
    }


    /**团购模块全新改版 发现页买单人气榜列表
     * @param array $param
     * @return mixed
     */
    public function getSearchTopList($param = [])
    {
        $params = [];
        $params['sort_id'] = $param['cat_id'] ?? 0;// 团购分类ID
        $params['city_id'] = $param['now_city'] ?? 0;//城市id
        $params['page'] = $param['page'] ?? 0;
        $params['pageSize'] = $param['pageSize'] ?? 10;
        $params['sort'] = isset($param['sort'])&&$param['sort'] ? $param['sort'] : 'sale';//   排序 智能排序：defaults 评价排序：score 默认 ：按销量最高低
        $params['status'] = 1;
        $params['is_not_cashing'] = 1;
        $params['no_booking_appoint'] = 1;

        $groupGoods = $this->getGroupGoodsList($params);
        $list = $groupGoods['list'];
        $count = $groupGoods['count'];
        $group_list = [];
        if (!empty($list)) {
            foreach ($list as $value) {
                $arr['group_id'] = $value['group_id'];
                $arr['name'] = $value['group_cate']=="course_appoint"?$value['s_name']:$value['name'];
                $arr['old_price'] = get_format_number($value['old_price']);
                $arr['price'] = get_format_number($value['price']);
                $arr['pic'] = $value['image'] ? thumb_img($value['image'], 400, 300, 'fill') : '';
                $arr['sale_count'] = $value['sale_count'] + $value['virtual_num'];
                $arr['url'] = cfg('site_url') . '/wap.php?g=Wap&c=Groupnew&a=detail&group_id=' . $value['group_id'] . '&page_num=1';
                $arr['buy_can_get_score'] = $value['buy_can_get_score'];
                $arr['buy_can_get_score_txt'] = $value['buy_can_get_score_txt'];
                $group_list[] = $arr;
            }
        }


        $returnArr = [];
        $returnArr['list'] = $group_list;
        $returnArr['count'] = $count;
        $returnArr['page'] = ceil($count / $params['pageSize']);
        return $returnArr;
    }

    /**
     * 获取团购模块全新改版 首页 频道页
     */
    public function getList($param = [])
    {
        $cat_id = $param['cat_id'] ?? 0;//0：首页 其它：频道页
        $user_long = $param['user_long'] ?? '';
        $user_lat = $param['user_lat'] ?? '';
        $city_id = isset($param['now_city']) ? intval($param['now_city']) : 0;//城市id

        $returnArr = [];
        // 轮播图
        if ($cat_id == 0) {
            $cat_key = 'wap_group_index_top';
            $banner_list = $this->getAdverList($cat_id, $cat_key, $city_id);
        } else {
            $banner_list = [];
        }
        $returnArr['banner_list'] = $banner_list;

        // 导航
        if ($cat_id == 0) {
            $cat_key = 'wap_group_index_nav';
            $cat_list = $this->getAdverList($cat_id, $cat_key, $city_id);
        } else {
            $cat_list = (new GroupCategoryService())->getSome($where = ['cat_fid' => $cat_id, 'cat_status' => 1], true, 'cat_sort desc');
            $cat_list = $this->groupCategoryFormat($cat_list);
        }
        $returnArr['cat_list'] = $cat_list;


        // 广告
        if ($cat_id == 0) {
            $cat_key = 'wap_group_index_adver';
            $ad_list = $this->getAdverList($cat_id, $cat_key, $city_id);
        } else {
            $cat_key = 'wap_group_channel_adver';
            $ad_list = $this->getAdverList($cat_id, $cat_key, $city_id);
        }
        $returnArr['ad_list'] = $ad_list;

        // 频道页热搜关键词
        if ($cat_id == 0) {
            $hot_words_list = [];
        } else {
            $hot_list = (new GroupSearchHotService())->getSome($where = ['cat_id' => $cat_id], true, 'sort desc');
            if (empty($hot_list)) {
                $hot_words_list = [];
            } else {
                foreach ($hot_list as $hot) {
                    $h['name'] = $hot['name'];
                    $hot_words_list[] = $h;
                }
            }
        }
        $returnArr['hot_words_list'] = $hot_words_list;

        // 频道页自定义颜色
        if ($cat_id == 0) {
            $returnArr['custome_color'] = '';
        } else {
            $group_category_info = (new GroupCategoryService())->getOne($where = ['cat_id' => $cat_id]);
            $returnArr['custome_color'] = empty($group_category_info) || empty($group_category_info['bg_color']) ? '' : $group_category_info['bg_color'];
        }

        $list = [];
        //查询附近好店是否显示
        $menu_show = (new Config())->where(['name'=> 'group_good_store_nearby'])->find();
        $store_list = [];
        if($menu_show&&$menu_show['value']==1) {
            // 0：首页 附近好店列表 其它：频道页 优选好店列表
            if ($cat_id == 0) {
                $store_list = (new MerchantStoreService())->getStoresDistance($where = " s.`status` = 1 AND s.city_id = {$city_id} AND s.have_group = 1 AND mm.status = 1", $user_long, $user_lat);
            } else {
                //获取频道分类含有团购商品的商家
                $condition = [];
                $condition[] = ['g.cat_id', '=', $cat_id];
                $condition[] = ['g.status', '=', 1];
                $condition[] = ['g.begin_time', '<', time()];
                $condition[] = ['g.end_time', '>', time()];
                if ($city_id > 0) {
                    $condition[] = ['m.city_id', '=', $city_id];
                }
                $group = $this->getGroupGoodsListByJoin($condition, $field = 'm.mer_id');

                $store_list = [];
                if (!empty($group)) {
                    $mer_group = array_unique(array_column($group, 'mer_id'));
                    $mer_where = [
                        ['mer_id', 'in', $mer_group],
                        ['status', '=', 1]
                    ];
                    //获取频道分类含有团购商品的店铺
                    $store = (new MerchantStore())->getSome($mer_where, true, 'store_id desc');
                    if (empty($store)) {
                        $store_list = [];
                    } else {
                        $store_id_arr = $store->toArray();
                        $store_id_arr = array_column($store_id_arr, 'store_id');
                        $store_id_str = implode(',', $store_id_arr);
                        $where = ' s.`status` = 1 AND mt.status=1 AND s.store_id IN (' . $store_id_str . ') AND s.have_group = 1';
                        $store_list = (new MerchantStoreService())->getStoresSelect($where, $user_long, $user_lat);
                    }
                }
            }
        }
        if (!empty($store_list)) {
            foreach ($store_list as &$store) {
                $store['logo'] = $store['logo'] ? thumb_img($store['logo'], 200, 200, 'fill') : '';
                if (!$store['logo'] && $store['pic_info']) {
                    $store['logo'] = thumb(explode(';', $store['pic_info'])[0], 200, 200, 'fill');
                }

                $store['score'] = empty($store['score']) ? '5.0' : get_format_number($store['score']);
                $distance = isset($store['distance']) ? sprintf("%.2f", $store['distance']) : '';
                $store['distance'] = $distance <= 0 ? '' : $distance . 'km';
                $store['url'] = cfg('site_url') . '/packapp/plat/pages/store/homePage?store_id=' . $store['store_id'];
            }
        }
        $list[] = [
            'type' => 1,
            'title' => $cat_id == 0 ? '附近好店' : '优选好店',
            'list' => $store_list
        ];


        // 特价拼团
        if ($cat_id == 0) {
            $discount = [];
            $discount['user_long'] = $user_long;
            $discount['user_lat'] = $user_lat;
            $discount['city_id'] = $city_id;
            $discount['page'] = 1;
            $discount['pageSize'] = 3;
            $discountList = $this->getDiscountGoods($discount);
        } else {
            $discount = [];
            $discount['user_long'] = $user_long;
            $discount['user_lat'] = $user_lat;
            $discount['city_id'] = $city_id;
            $discount['cat_id'] = $cat_id;
            $discount['page'] = 1;
            $discount['pageSize'] = 3;
            $discountList = $this->getDiscountGoods($discount);
        }
        $list[] = [
            'type' => 2,
            'title' => '特价拼团',
            'list' => $discountList['list']
        ];

        $returnArr['list'] = $list;
        
        //获取自定义头部配置
        $returnArr['top_config'] = (new GroupHomeMenuService())->getHomeTopConfig()['top_config'];
        
        return $returnArr;
    }


    /**获取广告位列表
     * @param $cat_id 0：首页 其它：频道页
     * @param $location 0：首页 1：发现页 2：频道页
     * @return array
     */
    public function getAdverList($cat_id, $cat_key, $city_id=0)
    {
        // 获取分类ID
        $adver_category = (new GroupAdverCategoryService())->getOne($where = ['cat_id' => $cat_id, 'cat_key' => $cat_key]);
        if (empty($adver_category)) {
            $list = [];
        } else {
            // 广告位列表
            $where = [
                ['cat_id', '=', $adver_category['id']],
                ['status', '=', 1]
            ];
            if(cfg('many_city')&&$city_id>0){
                $area_pid = (new Area())->where(['area_id'=>$city_id])->value('area_pid');
                $where[] = ['city_id','in',[$city_id,$area_pid,0]];
            }
            $adver_list = (new GroupAdverService())->getSome($where, true, 'cat_id desc,sort desc');
            if (empty($adver_list)) {
                $list = [];
            } else {
                $list = $this->formatList($adver_list);
            }
        }
        return $list;
    }


    /**获取团购商品筛选信息
     * @param $param
     * @return array
     */
    public function getScreenList($param)
    {
        $cat_id = $param['cat_id'] ?? 0;
        $returnArr = [];
        if(!empty($param['group_activity_recommendations'])){
            $returnArr = $this->groupActivityGoodsCustomScreen();
            return $returnArr;
        }
        //附近
        $returnArr['nearby'] = (new AreaService())->getAreaListByAreaPid();

        //团购商品分类
        $tmp_category_list = (new GroupCategoryService())->getAllCategory(['cat_status'=>1]);
        foreach ($tmp_category_list as $list) {
            $arr['cat_id'] = $list['cat_id'];
            $arr['cat_fid'] = $list['cat_fid'];
            $arr['cat_name'] = $list['cat_name'];
            if (empty($list['category_list'])) {
                $category = [];
            } else {
                $category = [];
                foreach ($list['category_list'] as $value) {
                    $l['cat_id'] = $value['cat_id'];
                    $l['cat_fid'] = $value['cat_fid'];
                    $l['cat_name'] = $value['cat_name'];
                    $category[] = $l;
                }
            }
            $arr['category_list'] = $category;
            $category_list[] = $arr;
        }

        unset($tmp_category_list);
        if ($cat_id == 0) {
            $returnArr['category_list'] = $category_list;
        } else {
            $category_list = array_column($category_list, NULL, 'cat_id');
            $returnArr['category_list'] = isset($category_list[$cat_id]['category_list']) ? $category_list[$cat_id]['category_list'] : $category_list;
        }


        //排序
        $returnArr['sort'] = [
            [
                "sort_name" => "智能排序",
                "sort_value" => "defaults",
            ],
            [
                "sort_name" => "评价排序",
                "sort_value" => "score",
            ],
            [
                "sort_name" => "离我最近排序",
                "sort_value" => "juli",
            ],
        ];
        return $returnArr;
    }
    
    public function groupActivityGoodsCustomScreen(){
        $returnArr = [
            'nearby' => [],
            'category_list' => [],
            'new_nearby' => [],
            'sort' => [],
            'score' => [],
        ];
        //附近
        $returnArr['new_nearby'] = [
            [
                "sort_name" => "附近",
                "sort_value" => "defaults",
                "sort" => "nearby",
            ],
        ];
        $nearby_arr = ['500m','1km','3km','5km','10km'];
        foreach ($nearby_arr as $val){
            $returnArr['new_nearby'][] = [
                "sort_name" => $val,
                "sort_value" => $val,
                "sort" => "nearby",
            ];
        }
        //智能排序
        $returnArr['sort'] = [
            [
                "sort_name" => "智能排序",
                "sort_value" => "defaults",
                "sort" => "brain_sort",
            ],
            [
                "sort_name" => "距离最近",
                "sort_value" => "distance",
                "sort" => "distance",
            ],
        ];
        //评分
        $returnArr['score'] = [
            [
                "sort_name" => "评分",
                "sort_value" => "defaults",
                "sort" => "score",
            ],
            [
                "sort_name" => "4.5分以上",
                "sort_value" => "4.5",
                "sort" => "score",
            ],
        ];
        return $returnArr;
    }

    /**获取团购商品列表
     * @param $param
     * @return array
     */
    public function getGoodsList($param)
    {
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 10;
        $start = ($page - 1) * $pageSize;
        $page = $param['page'] ?? 1;
        $tm = time();
        // 返回数据
        $returnArr = [];
        // 查询条件
        $where = [];
        // 排序
        $order = [
            'group_id' => 'DESC',
            'is_running' => 'DESC',
            'status' => 'DESC',
        ];
        if (isset($param['packageid'])) {
            $where[] = ['packageid', '=', $param['packageid']];
        }
        
        if(isset($param['mer_id']) && $param['mer_id']){// 商家id
            $where[] = ['mer_id', '=', $param['mer_id']];
        }

        if(isset($param['keyword']) && $param['keyword']){// 关键字查询
            $keyword = addslashes($param['keyword']);
            $where[] = ['s_name', 'exp', Db::raw('like "%' . $keyword . '%" OR name like "%'.$keyword.'%"')];
        }  
        
        if(isset($param['status']) && $param['status'] != -1){// 团购状态
            $where[] = ['status', '=', $param['status']];
        }

        if(isset($param['group_cate']) && $param['group_cate']){
            $where[] = ['group_cate', '=', $param['group_cate']];
        }

        if(isset($param['is_running']) && $param['is_running'] != -1){// 运行状态
            if($param['is_running'] == 1){
                $where[] = ['end_time', 'exp', Db::raw(' >= ' .$tm.' OR group_cate = "booking_appoint"')];
            }else{
                $where[] = ['end_time', 'exp',  Db::raw(' < ' .$tm.' AND group_cate <> "booking_appoint"')];
            }
        }

        $field = '*,end_time-'.$tm.' as is_running';
        $list = $this->getSome($where,$field,$order,$start,$pageSize);
        $total = $this->getCount($where);
        foreach($list as &$_goods){
            $_goods['group_cate_name'] = isset($this->groupCateNameArr[$_goods['group_cate']]) ? $this->groupCateNameArr[$_goods['group_cate']] : '';// 团购类型
            $_goods['status_str'] = $this->statusArr[$_goods['status']];// 团购状态
            // 运行状态
            if($_goods['end_time'] >= time() || $_goods['group_cate'] == 'booking_appoint'){
                $_goods['is_running'] = 1;
                $_goods['is_running_str'] = L_('运行中');
            }else{
                $_goods['is_running'] = 0;
                $_goods['is_running_str'] = L_('已结束');
            }
            $_goods['sale_count'] = $this->getSaleCount($_goods);
            // 评论数
            $where = [
                'o.group_id' => $_goods['group_id']
            ];
            $_goods['reply_count'] = (new ReplyService())->getCountByGroupGoods($where);// 评价总数
            $group_store=(new GroupStore())->getOne(['group_id'=>$_goods['group_id']]);
            if(empty($group_store)){
                $group_store=[];
            }
            $_goods['begin_time'] = date('Y-m-d H:i:s',$_goods['begin_time']);
            $_goods['end_time'] = date('Y-m-d H:i:s',$_goods['end_time']);
            $_goods['deadline_time'] = $_goods['deadline_time'] ? ($_goods['effective_type'] == 0 ? date('Y-m-d H:i:s',$_goods['deadline_time']) : $_goods['deadline_time']) : '-';// 有效期（当effective_type=1时为天数）
            if(in_array($_goods['group_cate'],['normal','course_appoint'])){
                $_goods['detail_url'] = get_base_url('',1).'pages/group/v1/groupDetail/index?group_id='.$_goods['group_id'];
            }elseif($_goods['trade_info'] == 'hotel'){
                $_goods['detail_url'] = cfg('site_url').'/wap.php?g=Wap&c=Group&a=detail&group_id='.$_goods['group_id'];
            }else{
                if(!empty($group_store)){
                    $_goods['detail_url'] = get_base_url('',1).'pages/store/v1/home/index?store_id='.$group_store['store_id'];
                }else{
                    $_goods['detail_url'] = get_base_url('',1).'pages/group/v1/groupDetail/index?group_id='.$_goods['group_id'];
                }
            }

            //场次预约价格、开始时间、结束时间、有效期特殊处理展示
            if($_goods['group_cate'] == 'booking_appoint'){
                $_goods['price'] = L_('多规格');
                $_goods['begin_time'] = date('Y-m-d H:i:s',$_goods['add_time']);
                $_goods['end_time'] = '-';
                $_goods['deadline_time'] = '-';
            }
                
        }

        
        $returnArr['list'] = $list;
        $returnArr['total'] = $total;
        return $returnArr;
    }
    
    /**格式化数据
     * @param $list
     * @return array
     */
    private function formatList($list)
    {
        if (empty($list)) {
            return [];
        }

        $returnArr = [];
        foreach ($list as $value) {
            $pic = $value['pic'] ? replace_file_domain($value['pic']) : '';
            if ($pic && substr($pic, 0, 4) != 'http') {
                $pic = cfg('site_url') . $pic;
            }
            $arr['cat_id'] = $value['id'];
            $arr['name'] = $value['name'];
            $arr['pic'] = $pic;
            $arr['url'] = $value['url'];
            $returnArr[] = $arr;
        }
        return $returnArr;
    }

    /**格式化数据
     * @param $list
     * @return array
     */
    private function groupCategoryFormat($list)
    {
        if (empty($list)) {
            return [];
        }


        $returnArr = [];
        foreach ($list as $value) {
            $arr['cat_id'] = $value['cat_id'];
            $arr['cat_fid'] = $value['cat_fid'];
            $arr['name'] = $value['cat_name'];
			
			if(strpos($value['cat_pic'], '/upload/') !== false){
				$arr['pic'] = replace_file_domain($value['cat_pic']);
			}else{
				$arr['pic'] = $value['cat_pic'] ? replace_file_domain('/upload/system/' . $value['cat_pic']) : '';
			}
            
            $arr['url'] = '';
            $returnArr[] = $arr;
        }
        return $returnArr;
    }


    /**
     * 获取单个数据信息
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     * @author: wanziyang
     * @date_time:  2020/6/15 11:27
     */
    public function getGroupOne($where, $field = true)
    {
        $info = $this->getOne($where, $field);

        return $info;
    }

    /**
     * 获取单个数据信息
     * @param array $where 查询条件
     * @return array
     * @author: 衡婷妹
     * @date_time:  2020/11/27 14:02
     */
    public function getGroupDetail($param)
    {
        $groupId = $param['group_id'] ?? 0;
        if (empty($groupId)) {
            return [];
        }

        // 获得详情数据
        $where = [
            'group_id' => $groupId
        ];
        $detail = $this->getOne($where);
        if (empty($detail)) {
            return $detail;
        }

        return $detail;
    }

    /**
     * 获得普通团购商品详情-团购商品
     * @param int $groupId 商品id
     * @param array $param 参数
     * @return array
     * @author: 衡婷妹
     * @date_time:  2021/04/29 09:39
     */
    public function getGoodsNormalDetail($groupId)
    {
        if (empty($groupId)) {
			throw new \think\Exception(L_("缺少参数"),1001);
        }

        // 获得详情数据
        $where = [
            'group_id' => $groupId
        ];
        $detail = $this->getOne($where);
        if (empty($detail)) {
			throw new \think\Exception(L_("商品不存在"),1001);
        }

        // 处理公共字段
        $detail = $this->dealCommonInfo($detail);

        // 处理个性化字段
        // 规格属性
        $where = [
            'group_id' => $groupId,
            'status' => 1
        ];
        $detail['spec_list'] = (new GroupSpecificationsService())->getSome($where);

        // 子标签
        $detail['label_ids'] = $detail['label_ids'] ? explode(',',$detail['label_ids']) : [];

        // 分销员
        $ratio_list = (new StoreMarketingRatio())->where(['is_del'=>0, 'goods_id'=>$groupId, 'type'=>1])->select();
        $ratioList = [];
        if($ratio_list){
            foreach($ratio_list as $v){
                $ratioList[] = array(
                    'id' => $v['person_id'],
                    'ratio' => $v['ratio'],
                );
            }
        }
        $detail['ratio_list'] = $ratioList;
        return $detail;
    }
    /**
     * 保存普通团购商品详情-团购商品
     * @param int $groupId 商品id
     * @param array $param 参数
     * @return array
     * @author: 衡婷妹
     * @date_time:  2021/04/29 09:39
     */
    public function saveNomalGoods($param = [], $merchantUser = [])
    {   
        // 商品id
        $groupId = $param['group_id'] ?? 0;

        // 保存数据数据
        $saveData = [];
        $saveData['s_name'] = $param['s_name'] ?? '';//商品名称
        $saveData['tuan_type'] = $param['tuan_type'] ?? '0';//团购类型，0为团购券，2为实物，3-兑换码
        $saveData['pass_num'] = $param['pass_num'] ?? '1';//为团购券时可生成的券码数量
        if(empty($saveData['tuan_type']) && empty($saveData['pass_num'])){
            $saveData['pass_num'] = 1;//没有设置券码数量，团购券默认为1
        }
        $saveData['label_group'] = $param['label_group'] ?? '0';//标签组id
        $saveData['label_ids'] = isset($param['label_ids']) && $param['label_ids'] ? implode(',', $param['label_ids']) : '';//商品子标签 id数组
        $saveData['open_express'] = $param['open_express'] ?? '0';//是否支持邮寄 1=是 0=否
        //非实物强制写死不支持邮寄
        if (in_array($param['tuan_type'], [0, 3])) {
            $saveData['open_express'] = 0;
        }
        $saveData['old_price'] = $param['old_price'] ?? '0';// 原价
        $saveData['price'] = $param['price'] ?? '0';// 团购价
        $saveData['price_range_low'] = $param['price_range_low'] ?? '0';// 团购最低价
        $saveData['price_range_height'] = $param['price_range_height'] ?? '0';// 团购最高价
        $saveData['marketing_ratio'] = $param['marketing_ratio'] ?? '0';// 分销员比例
        $ratioData['ratio_list'] = $param['ratio_list'] ?? [];// 分销员单独比例
        $saveData['pic'] = isset($param['image']) && $param['image'] ? implode(';', $param['image']) : '0';// 图片数组
        $saveData['is_invoice'] = $param['is_invoice'] ?? '0';// 是否提供发票 1=是 0=否
        $saveData['begin_time'] = isset($param['begin_time']) && $param['begin_time'] ? strtotime($param['begin_time']) : '0';// 团购开始时间
        $saveData['end_time'] = isset($param['end_time']) && $param['end_time']  ? strtotime($param['end_time']) : '0';// 团购结束时间
        $saveData['effective_type'] = $param['effective_type'] ?? '0';// 有效期类型 0=固定时间 1=领取多少天后失效 
        $saveData['deadline_time'] = $param['deadline_time'] ?? '';
        $saveData['deadline_time'] = isset($param['effective_type']) && $param['effective_type'] == 0 ? strtotime($saveData['deadline_time']) : $saveData['deadline_time'];// 有效期（当effective_type=1时为天数）
        $saveData['cancel_type'] = $param['cancel_type'] ?? '0';// 0=不可取消 1=到期前几小时取消 2=随时可取消
        $saveData['cancel_hours'] = $param['cancel_hours'] ?? '0';// 当cancel_type=1时，为小时数
        $saveData['appoint_time_type'] = $param['appoint_time_type'] ?? '0';// 提前预约时间类型：0=天1=小时
        $saveData['appoint_time'] = $param['appoint_time'] ?? '0';// 需提前预约时间：0=无需预约  >0时表示需提前多长时间预约
        $saveData['is_general'] = $param['is_general'] ?? '0';// 使用时间限制0-周末、法定节假日通用，1-周末不能使用，2-法定节假日不能使用，3-周末、法定节假日不能通用
        $saveData['content'] = isset($param['content']) && $param['content'] ? fulltext_filter($param['content']) : '';// 商品详情
        $saveData['count_num'] = $param['count_num'] ?? '0';// 商品总数
        $saveData['once_max'] = $param['once_max'] ?? '0';// 一个ID最多购买数量
        $saveData['once_max_day'] = $param['once_max_day'] ?? '0';// ID每天最多购买数量
        $saveData['once_min'] = $param['once_min'] ?? '0';// 一次最少购买数量
        $saveData['pin_num'] = $param['pin_num'] ?? '0';// 拼团人数 ：大于0表示开启拼团；等于0关闭拼团
        $saveData['pin_effective_time'] = isset($param['pin_effective_time']) && $param['pin_effective_time'] ? $param['pin_effective_time'] : '0';// 拼团有效期
        $saveData['start_discount'] = $param['start_discount'] ?? '0';// 团长优惠百分比
        $saveData['start_max_num'] = $param['start_max_num'] ?? '0';// 团购每次最多可购买数量
        $saveData['group_refund_fee'] = $param['group_refund_fee'] ?? '0';// 团后退款手续费比例
        $saveData['name'] = $param['name'] ?? '';// 商品标题
        $saveData['intro'] = $param['intro'] ?? '';// 商品简介
        $saveData['cat_id'] = $param['cat_id'] ?? '0';// 二级团购专业分类ID
        $saveData['cat_fid'] = $param['cat_fid'] ?? '0';// 一级团购专业分类ID
        $saveData['auto_check'] = $param['auto_check'] ?? '0';// 是否支持自动核销：0不支持，1支持
        $saveData['tagname'] = $param['tagname'] ?? '0';// 本团购套餐标签
        $saveData['packageid'] = $param['packageid'] ?? '0';// 选择的套餐id
        $saveData['express_template_id'] = $param['express_template_id'] ?? '0';// 	运费模板id
        $saveData['express_fee'] = $param['express_fee'] ?? '0';// 其他区域运费
        $saveData['pick_in_store'] = $param['pick_in_store'] ?? '0';// 开启到店自提1-开启0-关闭
        $saveData['status'] = $param['status'] ?? '0';// 商品状态
        $saveData['trade_type'] = $param['trade_type'] ?? '';// 绑定类型appoint-预约，hotel-酒店，空为不绑定
        $saveData['trade_info'] = $param['trade_info'] ?? '';// 绑定预约是预约的id；绑定酒店是酒店的分类id 多个逗号分隔
        $saveData['appoint_id'] = $param['appoint_id'] ?? '0';// 绑定预约id
        $saveData['is_appoint_bind'] = $param['is_appoint_bind'] ?? '0';// 是否绑定预约：大于0是绑定预约
        $saveData['stock_reduce_method'] = $param['stock_reduce_method'] ?? '0';// 库存减少方式（0：支付后减库存，1：下单即减库存）
        $saveData['is_give_limit'] = $param['is_give_limit'] ?? 1;// 转赠是否受限 0否1是
        $saveData['is_give'] = $param['is_give'] ?? 0;// 团购券是否可以转增0-否，1-是
        $saveData['is_sku'] = 0;// 0-无规格，1-多规格
        $saveData['wechat_share_img'] = $param['wechat_share_img']??''; //小程序分享图片
        $saveData['exchange_rules'] = $param['exchange_rules']??''; //兑换规则
        $exchange_num = $param['exchange_num'] ?? 0; //一份可兑换数量
        $exchange_code_url = $param['exchange_code_url'] ?? 0; //导入兑换码文件

        $saveData['last_time'] = time();
        if (empty($saveData['s_name'])) {
			throw new \think\Exception(L_("请填写商品名称"),1001);
        }

        if (empty($saveData['name'])) {
            throw new \think\Exception(L_("请填写商品标题[其他设置]"),1001);
        }

        if ($saveData['old_price'] < 0 && cfg('open_extra_price')==0) {
			throw new \think\Exception(L_("请填写商品原价"),1001);
        }

        if ($saveData['price'] < 0 && cfg('open_extra_price')==0) {
			throw new \think\Exception(L_("请填写商品X1价",cfg('group_alias_name')),1001);
        }

        if($saveData['tuan_type']==2 && $saveData['open_express']==0 && $saveData['pick_in_store']==0){
			throw new \think\Exception(L_("请选择一种配送方式"),1003);
        }

        if (empty($saveData['pic'])) {
			throw new \think\Exception(L_("请至少上传一张照片"),1003);
        }

        //商品总数量
        if ($saveData['count_num'] > 0 && $saveData['count_num'] < $saveData['once_min']) {
            throw new \think\Exception(L_("商品总数量不能小于一次最少购买数量"),1003);
        }

        if ($saveData['once_max'] > 0 && $saveData['once_min'] > $saveData['once_max']) {
            throw new \think\Exception(L_("ID最多购买数量不能小于一次最少购买数量"),1003);
        }
        
        if($saveData['is_appoint_bind'] && ($saveData['is_appoint_bind'] == 1)){
            if(!$saveData['appoint_id']){
                throw new \think\Exception(L_("绑定预约不能为空！"),1003);
            }
        }

        if(($saveData['packageid']>0) && empty($saveData['tagname'])){
            throw new \think\Exception(cfg('group_alias_name') .L_('套餐标签必须要写上！'), 1003);
        }

        if ($saveData['begin_time'] > $saveData['end_time']) {
            throw new \think\Exception(L_('结束时间必须大于开始时间！'), 1003);
        }

        if ($saveData['effective_type'] == 0 && $saveData['end_time'] > $saveData['deadline_time']) {
            throw new \think\Exception(cfg('group_alias_name') . L_('券有效期必须不小于结束时间！'), 1003);
        }
        if($saveData['price_range_low'] > 0){
            if(customization('store_marketing')){
                if($saveData['price_range_low'] > $saveData['price_range_height']){
                    throw new \think\Exception(cfg('group_alias_name') . L_('调价区间最高价不能低于调价区间最低价'), 1003);
                }
                if($saveData['price_range_low'] > $saveData['price']){
                    throw new \think\Exception(cfg('group_alias_name') . L_('售价不能小于调价区间最低价'), 1003);
                }
                if($saveData['price'] > $saveData['price_range_height']){
                    throw new \think\Exception(cfg('group_alias_name') . L_('售价不能高于调价区间最高价'), 1003);
                }
            }
        }

        if(!in_array($saveData['is_give'],array(1,0))){
            throw new \think\Exception(L_('转赠类型错误'), 1003);
        }

        if($saveData['pin_num']>0&&$saveData['pin_effective_time']<=0){
            throw new \think\Exception(L_('成团有效期必须大于0'), 1003);
        }

        // 状态
        if(cfg('group_verify') == 1 && $param['status'] == '1'){
            $saveData['status'] = isset($merchantUser['issign']) && $merchantUser['issign'] ? 1 : 2;
        }
        
        if($saveData['tuan_type']==3&&!$saveData['exchange_rules']){
            throw new \think\Exception(L_('兑换规则必填！'), 1003);
        }
        
        //查询配置
        $setInfo = (new StockWarn())->where(['mer_id'=>$merchantUser['mer_id']])->find();

        // 	会员优惠
        $leveloff = $param['leveloff_list'] ?? '';
        if($leveloff){
            $tempLeveloff = [];
            foreach($leveloff as $level){
                $level['type'] = intval($level['type']);
                $level['vv'] = intval($level['vv']);
                if (($level['type'] > 0) && ($level['vv'] > 0)) {
                    $tempLeveloff[$level['level']] = $level;
                }
            }
            $saveData['leveloff'] = $tempLeveloff ? serialize($tempLeveloff) : '';
        }else{
            $saveData['leveloff'] = '';
        }

        $sku_warn = 0;
        // 规格数组
        $specList = $param['spec_list'] ?? [];
        if ($specList) {
            if (count($specList) > 6) {
                throw new \think\Exception(L_('对应团购规格最多添加6个！'), 1003);
            }
            $specificationNameArr = array();// 所有规格名数组
            $specificationIdArr = [];// 已添加的规格id
            foreach ($specList as $val) {
                // 判断 规格名必须存在
                if (!isset($val['specifications_name']) && !$val['specifications_name']) {
                    throw new \think\Exception(L_('请填写对应规格名称！'), 1003);
                } elseif (in_array($val['specifications_name'], $specificationNameArr)) {
                    throw new \think\Exception(L_('【').$val['specifications_name'].L_('】规格名称重复！'), 1003);
                } elseif (!isset($val['price']) && !$val['price']) {
                    // 判断价格必须存在
                    throw new \think\Exception(L_('请填写【X1】规格价格！',$val['specifications_name']), 1003);
                } elseif ($val['count_num'] > 0 && $val['count_num'] < $val['once_min']) {
                    // 判断商品总数量不能小于一次最少购买数量
                    throw new \think\Exception(L_('商品总数量不能小于同一ID一次最少购买数量'), 1003);
                } elseif ($val['once_max'] > 0 && $val['once_max'] < $val['once_min']) {
                    // 判断同一ID最多购买数量不能小于一次最少购买数量
                    throw new \think\Exception(L_('同一ID最多购买数量不能小于一次最少购买数量'), 1003);
                } elseif ($val['once_max_day'] > 0 && $val['once_max_day'] < $val['once_min']) {
                    // 判断ID最多购买数量不能小于一次最少购买数量
                    throw new \think\Exception(L_('同一ID每日最多购买数量不能小于一次最少购买数量'), 1003);
                } elseif (!isset($val['once_min']) && $val['once_min'] <= 0) {
                    // 一次最少购买数量不得小于0
                    throw new \think\Exception(L_('一次最少购买数量不得小于0'), 1003);
                } elseif (isset($val['specifications_id']) && $val['specifications_id']) {
                    $specificationIdArr[] = $val['specifications_id'];
                } elseif (isset($val['price_range_low']) > 0){
                    if(customization('store_marketing')){
                        if (isset($val['price_range_low']) > $val['price_range_height']) {
                            throw new \think\Exception(L_('调价区间最高价不能低于调价区间最低价'), 1003);
                        } elseif (isset($val['price_range_low']) > $val['price']) {
                            return api_output_error(1003, "售价不能小于调价区间最低价");
                        } elseif (isset($val['price']) > $val['price_range_height']) {
                            return api_output_error(1003, "售价不能高于调价区间最高价");
                        }
                    } 
                }
                if(customization('store_marketing') && empty($val['price_range_low']) && $val['price']>0){
                    throw new \think\Exception(L_('请填写调价区间'), 1003);
                }
                $specificationNameArr[] = $val['specifications_name'];
                if($setInfo&&$val['count_num']<=$setInfo['min_num']){
                    $sku_warn = 1;
                }
            }
            $saveData['is_sku'] = 1;
        }

        // 不绑定插件
        if($saveData['trade_type']==''){
            $saveData['is_appoint_bind'] = 0;
            $saveData['appoint_id'] = 0;
            $saveData['trade_type'] = '';
        }

        // 店铺id
        $storeArr = $param['store_ids'] ?? []; 
        if (empty($storeArr)) {
			throw new \think\Exception(L_("请至少选择一家店铺"),1003);
        }
        $storeIds = array_column($storeArr,'store_id');
        $where = [['store_id' ,'in', implode(',',$storeIds )]];
        $storeList = (new MerchantStoreService())->getSome($where);
     
        if (empty($storeList)) {
            throw new \think\Exception(L_('您选择的店铺信息不正确！请重试。'), 1003);
        } else if($saveData['tuan_type'] == 2){
            $saveData['prefix_title'] = L_('购物');
        } else if (count($storeList) == 1) {
            $circleInfo = (new AreaService())->getAreaByAreaId($storeList[0]['circle_id']);
            if (empty($circleInfo)) {
                throw new \think\Exception(L_('您选择的店铺区域商圈信息不正确！请修改店铺资料后重试。'), 1003);
            }
            $saveData['prefix_title'] = $circleInfo['area_name'];
        } else {
            $saveData['prefix_title'] = count($storeList) . L_('店通用');
        }

        // 绑定插件处理
        switch($saveData['trade_type']){
            case '':
                $saveData['trade_type'] = 0;
                $saveData['trade_info'] = '';
                break;
            case 'appoint':
                if(empty($saveData['appoint_id'])){
                    throw new \think\Exception(L_('绑定预约不能为空！'), 1003);
                }
                $saveData['is_appoint_bind'] = 1;
                $saveData['trade_type'] = 'appoint';
                $saveData['trade_info'] = $saveData['appoint_id'];

                break;
            case 'hotel':
                if(empty($saveData['trade_info'])){
                    throw new \think\Exception(L_('请选择酒店分类'), 1003);
                }
                if($saveData['stock_reduce_method']==1){
                    throw new \think\Exception(L_('酒店库存仅支持支付完成后减库存'), 1003);
                }
                if($saveData['count_num']>0 ){
                    $saveData['count_num'] = 0;
                }
                $saveData['pin_num'] = 0;
                $saveData['pin_effective_time'] = 0;
                $saveData['trade_type'] = 'hotel';
                $saveData['is_appoint_bind'] = 0;
                $saveData['appoint_id'] = 0;
                break;
        }

        if (isset($saveData['pin_num']) && $saveData['pin_num']==1) {
            throw new \think\Exception(L_('拼团数量最少设置为2'), 1003);
        }

        // 保存主表数据
        $saveData['mer_id'] = $merchantUser['mer_id'];
        if($groupId){
            $where = [
                'group_id' => $groupId
            ];
            $detail = $this->getOne($where);
            if (empty($detail)) {
                throw new \think\Exception(L_("商品不存在"),1003);
            }
            if ($saveData['tuan_type'] == 3) {
                $saveData['count_num'] = $detail['count_num'];
            }
            $res = $this->updateThis($where, $saveData);
            if($res === false){
                throw new \think\Exception(L_("保存失败"),1001);
            }
            // 设置分销员单独比例
            if($ratioData['ratio_list']){
                (new StoreMarketingRatio())->where(['goods_id'=>$groupId, 'type' => 1])->delete();
                $ratioList = [];
                foreach($ratioData['ratio_list'] as $k=>$v){
                    $ratioList[$k] = array(
                        'goods_id' => $groupId,
                        'person_id' => $v['id'],
                        'ratio' => $v['ratio'],
                        'type' => 1,
                        'create_time' => time(),
                    );
                }
                (new StoreMarketingRatio())->addAll($ratioList);
            }
            if((!$specList&&$setInfo&&$saveData['count_num']>0&&($saveData['count_num']-$detail['sale_count'])>$setInfo['min_num'])||($specList&&$sku_warn == 1)){
                (new WarnNotice())->where(['mer_id'=>$saveData['mer_id'],'goods_id'=>$groupId,'business'=>'group','type'=>0,'status'=>1])->save(['status'=>0]);
            }
            $msg = L_("编辑成功");
        }else{
            $saveData['exchange_num'] = $exchange_num;
            $exchange_code_list = [];
            if($saveData['tuan_type']==3){
                if(!$exchange_code_url){
                    throw new \think\Exception(L_('请上传兑换码！'), 1003);
                }
                if($saveData['exchange_num']<1){
                    throw new \think\Exception(L_('兑换数量不能小于1！'), 1003);
                }
                $ex_time = time();
                foreach (getExcelDataForUrl($exchange_code_url, [
                    'A' => 'code',
                ]) as $data) {
                    foreach ($data as $item){
                        $exchange_code_list[] = [
                            'group_id' => 0,
                            'code' => $item['code'],
                            'status' => 0,
                            'create_time' => $ex_time,
                            'mer_id' => $saveData['mer_id'],
                        ];
                    }
                }
                if(!$exchange_code_list){
                    throw new \think\Exception(L_('导入兑换码为空！'), 1003);
                }
                $count_num = count($exchange_code_list) % $exchange_num;
                if($count_num > 0){
                    throw new \think\Exception(L_('券码导入数量需为生成券码单位的整数倍，否则将导致部分券码无法完成购买！'), 1003);
                }
                $saveData['count_num'] = $count_num;
            }
            $saveData['add_time'] = time();
            
            $groupId = $this->add($saveData);
            if(empty($groupId)){
                throw new \think\Exception(L_("添加失败"),1001);
            }
            
            if($exchange_code_list){
                foreach($exchange_code_list as $kk=>$vv){
                    $exchange_code_list[$kk]['group_id'] = $groupId;
                }
                (new GroupRedemptionCode())->saveAll($exchange_code_list);
            }
            // 设置分销员单独比例
            if($ratioData['ratio_list']){
                $ratioList = [];
                foreach($ratioData['ratio_list'] as $k=>$v){
                    $ratioList[$k] = array(
                        'goods_id' => $groupId,
                        'person_id' => $v['id'],
                        'ratio' => $v['ratio'],
                        'type' => 1,
                        'create_time' => time(),
                    );
                }
                (new StoreMarketingRatio())->addAll($ratioList);
            }
            $msg = L_("添加成功");
        }
				
        // 保存成功后其他数据处理

        // 绑定店铺
        $where = [
            'group_id' => $groupId
        ];

        // 查询原来的绑定
        $oldStoreList = (new StoreGroupService())->getSome($where);
        
        // 查询原来的店铺推荐
        $recommendList = array_column($oldStoreList,'is_rec','store_id');
       
        // 删除之前的
        (new StoreGroupService())->del($where);
        

        $dataGroupStore = [];
        $packageList = array_column($storeArr,'package_id','store_id');
        foreach ($storeList as $key => $value) {
            $temp = [];
            $temp['group_id'] = $groupId;
            $temp['store_id'] = $value['store_id'];
            $temp['province_id'] = $value['province_id'] ?? 0;
            $temp['city_id'] = $value['city_id'] ?? 0;
            $temp['area_id'] = $value['area_id'] ?? 0;
            $temp['circle_id'] = $value['circle_id'] ?? 0;
            $temp['package_id'] = isset($packageList[$value['store_id']]) ? $packageList[$value['store_id']] : 0;
            $temp['is_rec'] = isset($recommendList[$value['store_id']]) ? $recommendList[$value['store_id']] : 0;

            $dataGroupStore[] = $temp;
        }
        (new StoreGroupService())->addAll($dataGroupStore);

        //添加或删除到套餐
        $mpackageDb = new GroupPackagesService();
        if (isset($detail) && $detail['packageid'] > 0) {
            $where = [
                'id' => $saveData['packageid'],
                'mer_id' => $saveData['mer_id']
            ];
            $mpackage = $mpackageDb->getOne($where);
            
            if (!empty($mpackage)) { // 删除原有的
                $groupidtext = !empty($mpackage['groupidtext']) ? unserialize($mpackage['groupidtext']) : array();
                if(isset($groupidtext[$groupId])){
                    unset($groupidtext[$groupId]);
                }
                $mpackage['groupidtext'] = !empty($groupidtext) ? serialize($groupidtext) : '';
                $mpackageDb->updateThis(['id' => $mpackage['id']], ['groupidtext' => $mpackage['groupidtext']]);
            }
        }

        if ($saveData['packageid'] > 0) { // 现在编辑处理
            $where = [
                'id' => $saveData['packageid'],
                'mer_id' => $saveData['mer_id']
            ];
            $mpackage = $mpackageDb->getOne($where);
            if (!empty($mpackage)) {
                $mpackage['groupidtext'] = !empty($mpackage['groupidtext']) ? unserialize($mpackage['groupidtext']) : array();
                $mpackage['groupidtext'][$groupId] = $saveData['tagname'];
                $mpackageDb->updateThis(['id' => $mpackage['id']], ['groupidtext' => serialize($mpackage['groupidtext'])]);
            }
        }
        // 上传了规格信息 就对应添加或修改规格信息
        if ($specList) {
            $groupSpecificationsService = new GroupSpecificationsService();
            if ($specificationIdArr) {// 修改删除的规格状态
                $where = [
                    ['specifications_id' ,'not in' , $specificationIdArr],
                    ['group_id' ,'=' , $groupId],
                    ['status' ,'=' , 1]
                ];
                $groupSpecificationsService->updateThis($where,array('status' => 2));
            }
            $time = time();
            foreach ($specList as $val) {
                if (isset($val['specifications_id']) && $val['specifications_id']) { // 已有的
                    $specificationsSave = array(
                        'specifications_name' => $val['specifications_name'],
                        'price' => $val['price'],
                        'price_range_low' => $val['price_range_low'],
                        'price_range_height' => $val['price_range_height'],
                        'old_price' => $val['old_price'] ? $val['old_price'] : 0,
                        'count_num' => $val['count_num'] ? $val['count_num'] : 0,
                        'once_max' => $val['once_max'] ? $val['once_max'] : 0,
                        'once_max_day' => $val['once_max_day'] ? $val['once_max_day'] : 0,
                        'once_min' => $val['once_min'] ? $val['once_min'] : 0,
                        'last_time' => $time,
                        'status' => 1,
                    );
                    $groupSpecificationsService->updateThis(['specifications_id' => $val['specifications_id']],$specificationsSave);
                } else {
                    $specificationsSave = array(
                        'group_id' => $groupId,
                        'specifications_name' => $val['specifications_name'],
                        'price' => $val['price'],
                        'price_range_low' => $val['price_range_low'],
                        'price_range_height' => $val['price_range_height'],
                        'old_price' => $val['old_price'] ? $val['old_price'] : 0,
                        'count_num' => $val['count_num'] ? $val['count_num'] : 0,
                        'once_max' => $val['once_max'] ? $val['once_max'] : 0,
                        'once_max_day' => $val['once_max_day'] ? $val['once_max_day'] : 0,
                        'once_min' => $val['once_min'] ? $val['once_min'] : 0,
                        'add_time' => $time,
                        'status' => 1,
                    );
                    $groupSpecificationsService->add($specificationsSave);
                }
            }
        } else {
            // 没传 且开启多规格情况下 默认删除所有
            $groupSpecificationsService = new GroupSpecificationsService();
            $where = [
                ['group_id' ,'=' , $groupId],
                ['status' ,'=' , 1]
            ];
            $groupSpecificationsService->updateThis($where,array('status' => 2));
        }
        $this->addRedis($groupId);
        return ['msg'=>$msg];
    }

    /**
     * 获取状态
     * @param array $group
     * @return string
     * @author: 衡婷妹
     * @date_time:  2020/11/25 14:38
     */
    public function getStatus($group)
    {
        if ($group['begin_time'] > time()) {
            $return = L_('未开团');
        } elseif ($group['end_time'] < time()) {
            $return = L_('已过期');
        } elseif ($group['type'] == 3) {
            $return = L_('已结束');
        } elseif ($group['type'] == 4) {
            $return = L_('结束失败');
        } else {
            $return = L_('进行中');
        }
        return $return;
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getGroupGoodsList($param = [], $systemUser = [], $merchantUser = [], $type = 0,$group_by_store='')
    {
        $keywords = $param['keywords'] ?? '';
        $catFId = $param['cat_fid'] ?? '';
        $catId = $param['sort_id'] ?? '';
        $area_id = $param['area_id'] ?? '';
        $city_id = $param['city_id'] ?? '';
        $circle_id = $param['circle_id'] ?? 0;
        $page = $param['page'] ?? '1';
        $pageSize = $param['pageSize'] ?? '0';
        $groupType = $param['group_type'] ?? '0';
        $sort = $param['sort'] ?? '';
        $flag = $param['flag'] ?? '';
        $no_booking_appoint = $param['no_booking_appoint']??0;
        $group_cate = $param['group_cate']??'';
        $custom_id = $param['custom_id'] ?? 0; //团购首页装饰-商品活动推荐id（有值去除已选商品）
        $returnArr = [];

        $where = [];

        // 父分类
        if (!empty($catFId)) {
            $where[] = ['g.cat_fid', '=', $catFId];
        }

        // 分类
        if (!empty($catId)) {
            $where[] = ['g.cat_id', '=', $catId];
        }

        // 团购类型
        if (!empty($groupType)) {
            $where[] = ['g.group_type', '=', $groupType];
        }

        // 是否拼团
        if (isset($param['pin_num'])) {
            $where[] = ['g.pin_num', '=', $param['pin_num']];
        }

        // 是否拼团 特价拼团
        if (isset($param['pin_num_type']) && $param['pin_num_type'] == 1) {
            $where[] = ['g.pin_num', '>', 2];
        }

        // 状态
        if (isset($param['status'])) {
            $where[] = ['g.status', '=', $param['status']];
        }

        // 有效团购
        if (isset($param['status']) && $param['status'] == 1) {
            $where[] = ['g.begin_time', '<', time()];
            $where[] = ['g.end_time', '>', time()];
            $where[] = ['m.status', '=', 1];
            $where[] = ['ms.status', '=', 1];
            $where[] = ['ms.have_group', '=', 1];
        }

        // 是否有规格
        if (isset($param['has_spec'])) {
            $where[] = ['s.specifications_id', '=', null];
        }

        // 区域
        if (!empty($area_id)) {
            $where[] = ['g.area_id', '=', $area_id];
        }

        //商圈
        if ($circle_id) {
            $where[] = ['ms.circle_id', '=', $circle_id];
        }

        // 城市
        if (!empty($city_id) && !$type) {
            $where[] = ['m.city_id', '=', $city_id];
        }

        // 城市
        if (!empty($city_id) && $type) {
            $where[] = ['gs.city_id', '=', $city_id];
        }

        // 是否是预约酒店
        if (isset($param['trade_type'])) {
            if ($param['trade_type'] == 0) {
                $where[] = ['g.trade_type', 'exp', Db::raw('= 0 OR g.trade_type=""')];
            } else {
                $where[] = ['g.trade_type', '=', $param['trade_type']];
            }
        }
        
        if(!empty($param['is_not_cashing'])){
            $where[] = ['g.group_cate', '<>', 'cashing'];
        }

        if(!empty($group_cate)){
            $where[] = ['g.group_cate', '=', $group_cate];
        }

        // 关键词搜索
        if (!empty($keywords)) {
            $keywords = addslashes($keywords);
            $where[] = ['g.name', 'exp', Db::raw('like "%' . $keywords . '%" OR m.name like "%' . $keywords . '%" OR g.group_id like "%' . $keywords . '%"')];
        }

        //用户端不展示场次预约
        if($no_booking_appoint){
            $where[] = ['g.group_cate','<>','booking_appoint'];
        }

        //团购首页装饰-商品活动推荐id（有值去除已选商品）
        if($custom_id){
            $custom_goods_ids = (new GroupRenovationGoodsCustomTop())->where(['custom_id'=>$custom_id])->column('group_id');
            if($custom_goods_ids){
                $where[] = ['g.group_id','not in',$custom_goods_ids];
            }
        }

        $field = 'g.*,m.name as merchant_name';
        $order = [
            'g.group_id' => 'DESC'
        ];


        // 智能排序
        if (!empty($sort)) {
            if ($sort == 'sale') {
                $order = [
                    Db::raw('g.sale_count+g.virtual_num DESC')
                ];
            } elseif ($sort == 'score') {
                $order = [
                    'g.score_mean' => 'DESC',
                ];
            } elseif ($sort == 'defaults') {
                $order = [
                    'g.group_id' => 'DESC',
                ];
            }

        }

        // 列表
        $list = $this->getGroupGoodsListByJoin($where, $field, $order, $page, $pageSize, $type,$group_by_store);
        $groupImage = new GroupImageService();
        foreach ($list as &$_group) {
            $tmp_pic_arr = explode(';', $_group['pic']);
            $_group['image'] = $groupImage->getImageByPath($tmp_pic_arr[0], 's');
            $_group['status_str'] = $this->getStatus($_group);
            $_group['begin_time'] = date('Y-m-d H:i', $_group['begin_time']);
            $_group['end_time'] = date('Y-m-d H:i', $_group['end_time']);
            $_group['cfg_sort'] = 0;
            $_group['flag'] = $flag;
            //消费一元获得积分
            $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($_group['price'],'group',false,$_group['score_percent']);
            $_group['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
            $_group['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
        }
        // 总数
        $count = $this->getGroupGoodsCountByJoin($where, $type);
        $returnArr['list'] = $list;
        $returnArr['count'] = $count;
        return $returnArr;
    }

    /**
     * 获得添加编辑团购商品所需的数据
     * Author: 衡婷妹
     * @param $param array
     * @return array
     */
    public function getGroupEditInfo($merId){
        if(!$merId){
			throw new \think\Exception(L_("缺少参数"),1001);
        }

        $returnArr = [];

        //团购套餐
        $returnArr['packages_list'] = (new GroupPackagesService())->getList($merId)['list'];

        
        //团购专业分类列表
        $where = [];
        $where[] = ['mer_id', '=', intval($merId)];
        $returnArr['group_category_list'] = (new GroupCategoryService())->getCategoryTree();

        //插件绑定-预约列表
        $where = [];
        $where[] = ['mer_id', '=', intval($merId)];
        $where[] = ['start_time', '<', time()];
        $where[] = ['end_time', '>', time()];
        $where[] = ['appoint_status', '=', 0];
        $where[] = ['check_status', '=', 1];
        $where[] = ['end_time', '>', time()];
        $appointList = (new AppointService())->getSome($where);
        foreach($appointList as $val){
            $temp = [
                'appoint_name' => $val['appoint_name'],
                'appoint_id' => $val['appoint_id'],
            ];
            $returnArr['appoint_list'][] =  $temp;
        }

        //插件绑定-酒店分类列表
        $where = [];
        $where[] = ['mer_id', '=', intval($merId)];
        $where[] = ['is_remove', '=', 0];
        $where[] = ['cat_fid', '=', 0];
        $hotelCategory = (new TradeHotelCategoryService())->getSome($where,true,['cat_sort'=>'DESC']);
        foreach($hotelCategory as $val){
        $temp = [
            'cat_id' => $val['cat_id'],
            'cat_name' => $val['cat_name'],
            ];
            $returnArr['hotel_list'][] =  $temp;
        }

        //运费模板
        $expressList = (new ServiceExpressTemplateService())->getMerchantET($merId)['list'];
        foreach($expressList as $val){
            $temp = [
                'id' => $val['id'],
                'name' => $val['name'],
            ];
            $returnArr['express_list'][] =  $temp;
        }

        // 用户会员等级 user_level
        if (cfg('level_onoff') && cfg('group_level_onoff')) {
            $userLevel =  (new UserLevelService())->getSome([],true,['id'=>'ASC']);


            foreach ($userLevel as $level) {
                if(cfg('discount_sync')){//同步
                    $temp = [
                        'lid' => $level['id'],
                        'lname' => $level['lname'],
                        'type' => $level['type'],
                        'vv' => $level['boon'],
                        'level' => $level['level'],
                    ];
                }else{
                    $temp = [
                        'lid' => $level['id'],
                        'lname' => $level['lname'],
                        'type' => 0,
                        'vv' => '',
                        'level' => $level['level'],
                    ];
                }

                $returnArr['user_level'][] = $temp;
            }
        }

        $returnArr['store_marketing'] = customization('store_marketing') == 1 ? true : false;
        $returnArr['discount_sync_status'] = true;
//        if((cfg('discount_sync') == 1 && (!isset($_SESSION['system']) || empty($_SESSION['system']))) || (isset($_SESSION['system']) && $_SESSION['system'] && !cfg('discount_sync'))){
//            $returnArr['discount_sync_status'] = true;
//        }
        //修改为只有会员等级优惠不同步的时候才允许商家后台修改
        if(!cfg('discount_sync')){
            $returnArr['discount_sync_status'] = false;
        }
        //是否展示虚拟兑换码选项
        $returnArr['show_virtual_code_options'] = 0;
        if(strstr(cfg('site_url'),'a.qh2025.cn') || strstr(cfg('site_url'),'www.group.com')){
            $returnArr['show_virtual_code_options'] = 1;
        }
        return $returnArr;
    }
    
    
    /**
     * 获得商家的店铺列表
     * Author: 衡婷妹
     * @param $param array
     * @return array
     */
    public function getMerchantStoreList($param = []){
        $merId = $param['mer_id'] ?? 0;
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 10;
        if(!$merId){
			throw new \think\Exception(L_("缺少参数"),1001);
        }
        $where = [];
        $where[] = ['mer_id', '=', intval($merId)];
        $where[] = ['status', '<>', 4];
        if(isset($param['province_id']) && $param['province_id']){//省份ID
            $where[] = ['province_id', '=', intval($param['province_id'])];
        }
        if(isset($param['city_id']) && $param['city_id']){//城市ID
            $where[] = ['city_id', '=', intval($param['city_id'])];
        }
        if(isset($param['area_id']) && $param['area_id']){//区域ID
            $where[] = ['area_id', '=', intval($param['area_id'])];
        }
        if(isset($param['keyword'])&& $param['keyword']){//关键词搜索
            $where[] = ['name', 'like', '%'.$param['keyword'].'%'];
        }

        if(isset($param['group_id'])&& $param['group_id']){// 查询团购绑定的店铺
            $storeGroup = (new StoreGroupService())->getSome(['group_id' => $param['group_id']]);
            $storeIds = array_column($storeGroup,'store_id');
            if($storeIds){
                $where[] = ['store_id', 'in', implode(',',$storeIds)];
            }else{
                $where[] = ['store_id', '=', null];
            }

        }

        $returnArr['list'] = [];
        $list = (new MerchantStoreService())->getStoreList($where,$page,$pageSize);
        $storeIds = array_column($list,'store_id');
        foreach($list as $key => $_store){
            $temp = [
                'store_id' => $_store['store_id'],
                'name' => $_store['name'],
                'show'=>false
            ];
            $returnArr['list'][$key] = $temp;
        }
        $count = (new MerchantStoreService())->getCount($where);


        //餐饮套餐
        if($storeIds){
            $where['store_ids'] = implode(',',$storeIds);
            $where['is_order'] = 1;
            $where['status'] = 1;
            $packageList = (new FoodshopGoodsPackageService())->getPackageList($where)['list'];
            foreach ($packageList as $package)
            {
                if(isset($package['num']) && $package['num'] != 0){
                    $tempPackage = [
                        'id' => $package['id'],
                        'name' => $package['name'],
                    ];
                    foreach ($returnArr['list'] as $key => $_store)
                    {
                        if($_store['store_id'] == $package['store_id'] ){
                            if(empty($returnArr['list'][$key]['package_list'])){
                                $returnArr['list'][$key]['package_list'][] = ['id'=>0,'name'=>'不使用'];
                            }
                            $returnArr['list'][$key]['package_list'][] = $tempPackage;
                        }
                    }
                }
            }
        }
        

        $returnArr['total'] = $count;
        return $returnArr;
    }

    /**
     * 库存处理
     * @param $specificationsId int 规格id
     * @param $num int 修改数量
     * @param $type 操作类型 1-减少库存 2 增加库存
     * @return array
     */
    public function updateStock($groupId, $num, $type = 1)
    {

        if (empty($groupId) || empty($num)) {
            return false;
        }

        $where = [
            'group_id' => $groupId
        ];

        $groupInfo = $this->getOne($where);

        if ($type == 1) {
            // $changeNum = $groupInfo['sale_count'] + $num;
            $res = $this->groupModel->where($where)->inc('sale_count', $num)->update();
        } else {
            $res = $this->groupModel->where($where)->dec('sale_count', $num)->update();
            // $changeNum = max(0, $groupInfo['sell_count'] - $num);
        }
   
        if ($res === false) {
            return false;
        }

        // 更新销售总量
        $this->updateStockTotal($groupId, $num, $type);
        return true;
    }

    /**
     * 更新销售总量
     * @param $groupId int 商品id
     * @param $num int 修改数量
     * @param $type 操作类型 1-增加销量 2 减少销量
     * @return array
     */
    public function updateStockTotal($groupId, $num, $type = 1)
    {

        if (empty($groupId) || empty($num)) {
            return false;
        }

        $where = [
            'group_id' => $groupId
        ];

        if ($type == 1) {
            $res = $this->groupModel->where($where)->inc('sale_count_total', $num)->update();
        } else {
            $res = $this->groupModel->where($where)->dec('sale_count_total', $num)->update();
            $group = $this->groupModel->where($where)->findOrEmpty()->toArray();
            if($group&&$group['type']==3){
                (new Group())->where($where)->save(['type'=>1]);
            }
        }
   
        if ($res === false) {
            return false;
        }

        return true;
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getGroupCategoryList($param = [])
    {
        $catId = $param['cat_id'] ?? '0';

        $where = [];

        // 分类
        if ($catId > 0) {
            $where[] = ['g.cat_id', '=', $catId];
        }
        $where[] = ['c.cat_id', '<>', ''];
        $where[] = ['g.status', '=', '1'];
        $where[] = ['g.begin_time', '<', time()];
        $where[] = ['g.end_time', '>', time()];
        $field = 'c.cat_id,c.`cat_name`';

        // 列表
        $list = $this->getGroupCategoryListByJoin($where, $field);
        foreach ($list as &$value) {
            $value['cat_id'] = (string)$value['cat_id'];
        }
        $returnArr = [];
        $returnArr['list'] = $list;
        return $returnArr;
    }

    /**获取特价拼团列表
     * @param array $where 查询条件
     * @param float $lng 用户经度
     * @param float $lat 用户维度
     * @param int $limit 返回的列表数量
     * @return mixed
     *
     */
    public function getGroupsDistance($where = [], $lng, $lat, $page = 0, $limit = 10, $order = 's.score_mean DESC,s.sale_count DESC')
    {
        return $this->groupModel->getGroupsDistance($where, $lng, $lat, $page, $limit, $order);
    }

    /**
     *根据条件返回列表
     * @param $where array
     * @return array
     */
    public function getGroupGoodsListByJoin($where, $field = '', $order = ['g.group_id' => 'DESC'], $page = 0, $pageSize = 0, $type = 0,$group_by_store='')
    {

        if (empty($order)) {
            $order = ['o.group_id' => 'DESC'];
        }
        if ($type) {
            $field .= ',gs.store_id';
            $orderList = $this->groupModel->getGroupGoodsListByJoin1($where, $field, $order, $page, $pageSize,$group_by_store);
        } else {
            $orderList = $this->groupModel->getGroupGoodsListByJoin($where, $field, $order, $page, $pageSize);
        }
        if (!$orderList) {
            return [];
        }
        return $orderList->toArray();
    }

    /**
     *根据条件返回列表
     * @param $where array
     * @return array
     */
    public function getGroupCategoryListByJoin($where, $field = '', $order = ['c.cat_sort' => 'DESC'], $page = 0, $pageSize = 0)
    {

        $categoryList = $this->groupModel->getGroupCategoryListByJoin($where, $field, $order, $page, $pageSize);
        if (!$categoryList) {
            return [];
        }
        return $categoryList->toArray();
    }


    /**
     *根据条件返回总数
     * @param $where array
     * @return array
     */
    public function getGroupGoodsCountByJoin($where, $type = 0)
    {
        if ($type) {
            $count = $this->groupModel->getGroupGoodsCountByJoin1($where);
        } else {
            $count = $this->groupModel->getGroupGoodsCountByJoin($where);
        }
        if (!$count) {
            return 0;
        }
        return $count;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data)
    {
        $id = $this->groupModel->insertGetId($data);
        if (!$id) {
            return false;
        }

        return $id;
    }

    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function addAll($data)
    {
        if (empty($data)) {
            return false;
        }

        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->groupModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * 更新数据
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data)
    {
        if (empty($data) || empty($where)) {
            return false;
        }

        try {
            $result = $this->groupModel->where($where)->update($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = [])
    {
        if (empty($where)) {
            return false;
        }

        $result = $this->groupModel->getOne($where, $order);
        if (empty($result)) {
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where, $field = true, $order = true, $page = 0, $limit = 0)
    {
        try {
            $result = $this->groupModel->getSome($where, $field, $order, $page, $limit);
//            var_dump($this->groupModel->getLastSql());
        } catch (\Exception $e) {
            return [];
        }

        return $result->toArray();
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getCount($where)
    {
        $count = $this->groupModel->getCount($where);
        if (!$count) {
            return 0;
        }
        return $count;
    }


    /**
     * 获取代金券详情
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function getGoodsCashingDetail($param){
        if(empty($param['group_id'])) throw new \Exception("参数不正确");

        //1、获取商品详情
        $group_info = $this->groupModel->getOne(['group_id' => $param['group_id'], 'group_cate'=> 'cashing', 'mer_id'=> $param['mer_id']]);
        if(empty($group_info)){
            throw new \Exception("参数不正确");            
        }
        $group_info = $group_info->toArray();

        //2、处理团购商品的公共信息
        $group_info = $this->dealCommonInfo($group_info);

        //3、处理代金券商品的个性化字段
        $group_info['face_value'] = $group_info['face_value'] ? get_format_number($group_info['face_value']) : '0';

        $group_info['is_invoice'] = get_format_number($group_info['is_invoice']);
        $group_info['effective_type'] = (int)$group_info['effective_type'];
        $group_info['cancel_type'] = get_format_number($group_info['cancel_type']);
        $group_info['is_general'] = get_format_number($group_info['is_general']);
        $group_info['appoint_time_type'] = get_format_number($group_info['appoint_time_type']);
        $group_info['pic'] = $group_info['pic']?replace_file_domain($group_info['pic']):'';
        
        return $group_info;
    }

    /**
     * 处理团购商品的公共商品信息字段
     * @param  [type] $group 商品信息
     * @return [type]        [description]
     */
    public function dealCommonInfo($group){
        /**
         * 这里编写公共字段的处理逻辑
         */
        
        // 团购价
        $group['price'] = isset($group['price']) ? get_format_number($group['price']) : '0';
        
        // 原价
        $group['old_price'] = isset($group['old_price']) ? get_format_number($group['old_price']) : '0';

        // 团购开始时间
        $group['is_start'] = 0;
        if($group['begin_time'] <= time()){
            $group['is_start'] = 1;
        }
        $group['begin_time'] = isset($group['begin_time']) ? date('Y-m-d H:i:s',$group['begin_time']) : '';

        // 团购结束时间
        $group['end_time'] = isset($group['begin_time']) ? date('Y-m-d H:i:s',$group['end_time']) : '';

        // 有效期
        $group['deadline_time'] = isset($group['begin_time']) && $group['effective_type'] == 0 ? date('Y-m-d H:i:s',$group['deadline_time']) : $group['deadline_time'];

        //视频和封面图
        isset($group['video_image']) && $group['video_image'] = replace_file_domain($group['video_image']);
        isset($group['video_url']) && $group['video_url'] = replace_file_domain($group['video_url']);

        // 商品图片
        $group['image'] = [];
        if(isset($group['pic']) && $group['pic']){
            $groupImage = new GroupImageService();
            $tmpPicArr = explode(';', $group['pic']);
            foreach ($tmpPicArr as $value) {
                $group['image'][] = replace_file_domain($groupImage->getImageByPath($value,'s'));
            }
        }

        /*处理图片上传阿里云导致详情中的图片不展示*/
        $group['content'] = isset($group['content']) ?replace_file_domain_content($group['content']) : '';

        //会员优惠规则
        $leveloff = [];
        if(isset($group['leveloff']) && $group['leveloff']){
            $leveloff = unserialize($group['leveloff']);
        }

        // 用户会员等级
        $levelarr = [];
        if (cfg('level_onoff') && cfg('group_level_onoff')) {
           $userLevel =  (new UserLevelService())->getSome([],true,['id'=>'ASC']);
           foreach ($userLevel as $level) {
               $temp = [
                   'lid' => $level['id'],
                   'lname' => $level['lname'],
                   'type' =>'',
                   'vv' => '',
                   'level' => $level['level'],
               ];
               if($leveloff){
                   foreach ($leveloff as $leoffk => $leoff) {
                       if ($level['level'] == $leoffk) {
                           $temp['type'] = $leoff['type'] ?? '';
                           $temp['vv'] = $leoff['vv'] ?? '';
                       }
                   }
               }
               $levelarr[] = $temp;
           }
        }
        $group['leveloff_list'] = $levelarr;

        // 绑定店铺
        $store = [
            'ids' => [],//这里存放已经选择了的店铺的ID
            'detail' => [],//用于展示选择的店铺信息
        ];
        $where = [
            'group_ids' => [$group['group_id']]
        ];
        $storeList = (new StoreGroupService())->getStoreByGroup($where);
        if($storeList){
            foreach($storeList as $_store){
                $store['ids'][] = [
                    'store_id' => $_store['store_id'] ?? 0,
                    'package_id' => $_store['package_id'] ?? 0,
                ];
                $store['detail'][] = [
                    'store_id' => $_store['store_id'] ?? 0,
                    'name' => $_store['name'] ?? '',
                ];
            }
        }
        $group['store'] = $store;
        
        //微信图片
        $group['wechat_share_img'] = $group['wechat_share_img']?replace_file_domain($group['wechat_share_img']):'';


        return $group;
    }

    /**
     * 添加/编辑代金券
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function submitCashing($param){
        if(empty($param['face_value'])){
            throw new \think\Exception("面值未填写", 1003);            
        }
        if(empty($param['old_price'])){
            throw new \think\Exception("原价未填写", 1003);            
        }
        if(empty($param['price'])){
            throw new \think\Exception("团购价未填写", 1003);            
        }
        if(empty($param['begin_time'])){
            throw new \think\Exception("团购开始时间未填写", 1003);            
        }
        if(empty($param['end_time'])){
            throw new \think\Exception("团购结束时间未填写", 1003);            
        }
        if(empty($param['deadline_time'])){
            throw new \think\Exception("有效期未填写", 1003);            
        }
        if(!in_array($param['cancel_type'], [0,1,2])){
            throw new \think\Exception("取消类型不允许", 1003);            
        }
        // if(empty($param['s_name'])){
        //     throw new \think\Exception("团购标题未填写", 1003);            
        // }
        // if(empty($param['cat_id'])){
        //     throw new \think\Exception("团购分类未选择", 1003);            
        // }
        // if(empty($param['cat_fid'])){
        //     throw new \think\Exception("团购分类未选择", 1003);            
        // }

        $data = [
            'mer_id'            => $param['mer_id'],
            'group_cate'        => 'cashing',
            'face_value'        => $param['face_value'] ?? 0,
            'old_price'         => $param['old_price'] ?? 0,
            'price'             => $param['price'] ?? 0,
            'is_invoice'        => $param['is_invoice'] ?? 0,
            'begin_time'        => strtotime($param['begin_time']),
            'end_time'          => strtotime($param['end_time']),
            'effective_type'    => $param['effective_type'] ?? 0,
            'deadline_time'     => $param['effective_type'] == 1 ? $param['deadline_time'] : strtotime($param['deadline_time']),
            'cancel_type'       => $param['cancel_type'] ?? 0,
            'cancel_hours'      => $param['cancel_type'] == 1 ? $param['cancel_hours'] : 0,
            'appoint_time'      => $param['appoint_time'] ?? 0,
            'appoint_time_type' => $param['appoint_time_type'] ?? 0,
            'is_general'        => $param['is_general'] ?? 0,
            'content'           => $param['content'] ?? '',
            'count_num'         => $param['count_num'] ?? 0,
            'once_max'          => $param['once_max'] ?? 0,
            'once_max_day'      => $param['once_max_day'] ?? 0,
            'once_min'          => $param['once_min'] ?? 0,
            'once_use_max'      => $param['once_use_max'] ?? 0,
            's_name'            => trim($param['s_name']),
            'name'              => trim($param['name']),
            'intro'             => trim($param['intro']),
            'cat_id'            => $param['cat_id'] ?? 0,
            'cat_fid'           => $param['cat_fid'] ?? 0,
            'auto_check'        => $param['auto_check'] ?? 0,
            'tagname'           => $param['tagname'],
            'packageid'         => $param['packageid'] ?? 0,
            'status'            => $param['status'],
            'pic'               => $param['pic']??'',
        ];
        if($param['count_num_type']==0){
            $data['count_num']=0;
        }

        if($param['once_max_type']==0){
            $data['once_max']=0;
        }

        if($param['once_max_day_type']==0){
            $data['once_max_day']=0;
        }

        if($param['once_min_type']==0){
            $data['once_min']=0;
        }

        if($param['once_use_max_type']==0){
            $data['once_use_max']=0;
        }
        //  会员优惠
        $leveloff = $param['leveloff_list'] ?? '';
        if($leveloff){
            $tempLeveloff = [];
            foreach($leveloff as $level){
                $level['type'] = intval($level['type']);
                $level['vv'] = intval($level['vv']);
                if (($level['type'] > 0) && ($level['vv'] > 0)) {
                    $tempLeveloff[$level['level']] = $level;
                }
            }
            $data['leveloff'] = $tempLeveloff ? serialize($tempLeveloff) : '';
        }else{
            $data['leveloff'] = '';
        }

        // 店铺id
        $storeArr = $param['store_ids'] ?? []; 
        if (empty($storeArr)) {
            throw new \think\Exception(L_("请至少选择一家店铺"),1003);
        }
        $storeIds = array_column($storeArr,'store_id');
        $where = [['store_id' ,'in', implode(',',$storeIds )]];
        $storeList = (new MerchantStoreService())->getSome($where);
     
        if (empty($storeList)) {
            throw new \think\Exception(L_('您选择的店铺信息不正确！请重试。'));
        } else if (count($storeList) == 1) {
            $circleInfo = (new AreaService())->getAreaByAreaId($storeList[0]['circle_id']);
            if (empty($circleInfo)) {
                throw new \think\Exception(L_('您选择的店铺区域商圈信息不正确！请修改店铺资料后重试。'));
            }
            $data['prefix_title'] = $circleInfo['area_name'];
        } else {
            $data['prefix_title'] = count($storeList) . L_('店通用');
        }

        $res = 0;
        if(isset($param['group_id']) && $param['group_id'] > 0){//编辑
            $res = $this->groupModel->updateThis(['group_id'=>$param['group_id']], $data);
        }
        else{//新增
            $res = $this->groupModel->add($data);
            $param['group_id'] = $res;
        }

        // 更新Redis缓存
        $this->addRedis($param['group_id']);

        if($res > 0){
            // 绑定店铺
            // 删除之前数据
            $where = [
                'group_id' => $param['group_id']
            ];

            // 查询原来的绑定
            $oldStoreList = (new StoreGroupService())->getSome($where);
            
            // 查询原来的店铺推荐
            $recommendList = array_column($oldStoreList,'is_rec','store_id');

            (new StoreGroupService())->del($where);
            $dataGroupStore = [];
            $packageList = array_column($storeArr,'package_id','store_id');
            foreach ($storeList as $key => $value) {
                $temp = [];
                $temp['group_id'] = $param['group_id'];
                $temp['store_id'] = $value['store_id'];
                $temp['province_id'] = $value['province_id'] ?? 0;
                $temp['city_id'] = $value['city_id'] ?? 0;
                $temp['area_id'] = $value['area_id'] ?? 0;
                $temp['circle_id'] = $value['circle_id'] ?? 0;
                $temp['package_id'] = isset($packageList[$value['store_id']]) ? $packageList[$value['store_id']] : 0;
                $temp['is_rec'] = isset($recommendList[$value['store_id']]) ? $recommendList[$value['store_id']] : 0;
                $dataGroupStore[] = $temp;
            }
            (new StoreGroupService())->addAll($dataGroupStore);

            //添加或删除到套餐
            $mpackageDb = new GroupPackagesService();
            if ($param['packageid'] > 0) {
                $where = [
                    'id' => $param['packageid'],
                    'mer_id' => $param['mer_id']
                ];
                $mpackage = $mpackageDb->getOne($where);
                
                if (!empty($mpackage)) { // 删除原有的
                    $groupidtext = !empty($mpackage['groupidtext']) ? unserialize($mpackage['groupidtext']) : array();
                    if(isset($groupidtext[$param['group_id']])){
                        unset($groupidtext[$param['group_id']]);
                    }
                    $mpackage['groupidtext'] = !empty($groupidtext) ? serialize($groupidtext) : '';
                    $mpackageDb->updateThis(['id' => $mpackage['id']], ['groupidtext' => $mpackage['groupidtext']]);
                }
            }

            if ($param['packageid'] > 0) { // 现在编辑处理
                $where = [
                    'id' => $param['packageid'],
                    'mer_id' => $param['mer_id']
                ];
                $mpackage = $mpackageDb->getOne($where);
                if (!empty($mpackage)) {
                    $mpackage['groupidtext'] = !empty($mpackage['groupidtext']) ? unserialize($mpackage['groupidtext']) : array();
                    $mpackage['groupidtext'][$param['group_id']] = $data['tagname'];
                    $mpackageDb->updateThis(['id' => $mpackage['id']], ['groupidtext' => serialize($mpackage['groupidtext'])]);
                }
            }
            return true;
        }
        return false;
    }


    /**
     * 新增/编辑场次预约
     *
     * @param array $param
     * @return void
     * @author: 张涛
     * @date: 2021/04/29
     */
    public function saveBookingAppoint($param)
    {
        if (!isset($param['s_name']) || empty($param['s_name'])) {
            throw new Exception(L_("场次名称不能为空"), 1003);
        }
        if (!isset($param['rules']) || empty($param['rules'])) {
            throw new Exception(L_("场次设置不能为空"), 1003);
        }
        if (!isset($param['appoint_time']) || $param['appoint_time'] < 0) {
            throw new Exception(L_("可提前预约时长必须为大于或等于0的整数"), 1003);
        }
        if (!isset($param['store_ids']) || empty($param['store_ids'])) {
            throw new Exception(L_("请至少选择一家店铺"), 1003);
        }
        // if ($param['open_pin'] == 1 && $param['pin_num'] < 2) {
        //     throw new Exception(L_("拼团人数至少2人"), 1003);
        // }

        $tm = time();
        $data = [
            'mer_id' => $param['mer_id'],
            's_name' => $param['s_name'],
            'group_cate' => 'booking_appoint',
            'appoint_time' => $param['appoint_time'],
            'cancel_type' => $param['cancel_type'],
            'cancel_hours' => $param['cancel_hours'],
            //'open_pin' => $param['open_pin'],
            //'pin_num' => $param['open_pin'] == 1 ? $param['pin_num'] : 0,
            //'start_discount' => $param['start_discount'],
            //'start_max_num' => $param['start_max_num'],
            //'group_refund_fee' => $param['group_refund_fee'],
            //'pin_effective_time' => $param['pin_effective_time'],
            'once_max' => $param['once_max'],
            'once_max_day' => $param['once_max_day'],
            'once_min' => $param['once_min'],
            'stock_reduce_method' => $param['stock_reduce_method'],
            'name' => $param['name'] ?? '',
            'intro' => $param['intro'] ?? '',
            'cat_fid' => $param['cat_fid'],
            'cat_id' => $param['cat_id'],
            'auto_check' => $param['auto_check'],
            //'tagname' => $param['tagname'],
            //'packageid' => $param['packageid'],
            'status' => $param['status'],
            'last_time'=>$tm,
        ];

        Db::startTrans();
        try {
            //  会员优惠
            $leveloff = $param['leveloff_list'] ?? '';
            if ($leveloff) {
                $data['leveloff'] = serialize($leveloff);
            } else {
                $data['leveloff'] = '';
            }

            // 店铺id
            $storeIds = array_unique(array_column($param['store_ids'], 'store_id'));
            $where = [['store_id', 'in', $storeIds]];
            $storeList = (new MerchantStoreService())->getSome($where);
            if (empty($storeList)) {
                throw new Exception(L_('您选择的店铺信息不正确！请重试。'));
            } else if (count($storeList) == 1) {
                $circleInfo = (new AreaService())->getAreaByAreaId($storeList[0]['circle_id']);
                if (empty($circleInfo)) {
                    throw new Exception(L_('您选择的店铺区域商圈信息不正确！请修改店铺资料后重试。'));
                }
                $data['prefix_title'] = $circleInfo['area_name'];
            } else {
                $data['prefix_title'] = count($storeList) . L_('店通用');
            }

            $groupId = $param['group_id'] ?? 0;
            if ($groupId > 0) {//编辑
                $this->groupModel->updateThis(['group_id' => $groupId], $data);
            } else {//新增
                $data['add_time'] = $tm;
                $groupId = $this->groupModel->add($data);
                $param['group_id'] = $groupId;
            }

            //绑定店铺
            $where = ['group_id' => $param['group_id']];

            // 查询原来的绑定
            $oldStoreList = (new StoreGroupService())->getSome($where);
            
            // 查询原来的店铺推荐
            $recommendList = array_column($oldStoreList,'is_rec','store_id');

            (new StoreGroupService())->del($where);
            $dataGroupStore = [];
            $packageList = array_column($param['store_ids'], 'package_id', 'store_id');
            foreach ($storeList as $key => $value) {
                $temp = [];
                $temp['group_id'] = $param['group_id'];
                $temp['store_id'] = $value['store_id'];
                $temp['province_id'] = $value['province_id'] ?? 0;
                $temp['city_id'] = $value['city_id'] ?? 0;
                $temp['area_id'] = $value['area_id'] ?? 0;
                $temp['circle_id'] = $value['circle_id'] ?? 0;
                $temp['package_id'] = $packageList[$value['store_id']] ?? 0;
                $temp['is_rec'] = isset($recommendList[$value['store_id']]) ? $recommendList[$value['store_id']] : 0;
                $dataGroupStore[] = $temp;
            }
            (new StoreGroupService())->addAll($dataGroupStore);

            $ruleMod = new GroupBookingAppointRule();
            $ruleCombineMod = new GroupBookingAppointRuleCombine();
            $ruleDetailMod = new GroupBookingAppointRuleDetail();
            $combineMod = new GroupBookingAppointCombine();
            //保存场次，先删除旧的
            $rules = $param['rules'] ?? [];
            $ruleMod->where('group_id', $groupId)->update(['is_del' => 1]);
            $ruleIndexMap = [];
            if ($rules) {
                foreach ($rules as $r) {
                    $data = [
                        'group_id' => $groupId,
                        'start_time' => $r['start_time'],
                        'end_time' => $r['end_time'],
                        'use_hours' => $r['use_hours'],
                        'count' => $r['count'],
                        'default_price' => $r['default_price'],
                        'is_del' => 0
                    ];
                    if ($r['rule_id'] > 0) {
                        $ruleMod->where('rule_id', $r['rule_id'])->update($data);
                        $ruleId = $r['rule_id'];
                    } else {
                        $ruleId = $ruleMod->insertGetId($data);
                    }
                    $ruleIndexMap[$r['rule_index']] = $ruleId;

                    if($r['price_calendar']){
                        $this->updatePriceCalendar($groupId, $ruleId, $r['price_calendar']);
                    }
                }
            }

            //保存套餐
            $combineIndexMap = [];
            $combine = $param['combine'] ?? [];
            $combineMod->where('group_id', $groupId)->update(['is_del' => 1]);
            if ($combine) {
                foreach ($combine as $r) {
                    $data = [
                        'group_id' => $groupId,
                        'name' => $r['name'],
                        'intro' => $r['intro'],
                        'price' => $r['price'],
                        'is_del' => 0
                    ];
                    if ($r['combine_id'] > 0) {
                        $combineMod->where('combine_id', $r['combine_id'])->update($data);
                        $combineId = $r['combine_id'];
                    } else {
                        $combineId = $combineMod->insertGetId($data);
                    }
                    $combineIndexMap[$r['combine_index']] = $combineId;
                }
            }

            //保存场次-套餐组合
            $ruleCombine = $param['rule_combine'] ?? [];
            $ruleCombineMod->where('group_id', $groupId)->update(['is_del' => 1]);
            foreach ($ruleCombine as $rc) {
                if (isset($ruleIndexMap[$rc['rule_index']]) && isset($combineIndexMap[$rc['combine_index']])) {
                    $thisRuleId = $ruleIndexMap[$rc['rule_index']];
                    $thisCombineId = $combineIndexMap[$rc['combine_index']];

                    $ruleCombineData = [
                        'group_id' => $groupId,
                        'rule_id' => $thisRuleId,
                        'combine_id' => $thisCombineId,
                        'stock_num' => $rc['stock_num'],
                        'once_max' => $rc['once_max'],
                        'once_max_day' => $rc['once_max_day'],
                        'once_min' => $rc['once_min'],
                        'is_del' => 0
                    ];
                    if ($ruleCombineMod->where('group_id', $groupId)->where('rule_id', $thisRuleId)->where('combine_id', $thisCombineId)->find()) {
                        $ruleCombineMod->where('group_id', $groupId)->where('rule_id', $thisRuleId)->where('combine_id', $thisCombineId)->update($ruleCombineData);
                    } else {
                        $ruleCombineMod->insert($ruleCombineData);
                    }
                }
            }

            // 更新Redis缓存
            $this->addRedis($groupId);

            Db::commit();

            return true;
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage(), 1003);
        }
    }

    /**
     * 场次预约详情
     *
     * @param int $groupId
     * @return void
     * @author: 张涛
     * @date: 2021/04/29
     */
    public function showBookingAppoint($groupId)
    {
        if (empty($groupId)) {
            throw new Exception(L_("缺少参数"), 1001);
        }
        // 获得详情数据
        $detail = $this->getOne(['group_id' => $groupId]);
        if (empty($detail)) {
            throw new Exception(L_("商品不存在"), 1001);
        }
        // 处理公共字段
        $detail = $this->dealCommonInfo($detail);

        //过滤一下字段，场次预约用不到那么多字段
        $fields = [
            'group_id', 'appoint_time', 'cancel_type', 'cancel_hours', 's_name', 'open_pin', 'pin_num', 'start_discount', 'start_max_num', 'group_refund_fee', 'pin_effective_time', 'count_num', 'once_max', 'once_max_day',
            'once_min', 'stock_reduce_method', 'name', 'intro', 'cat_fid', 'cat_id', 'auto_check', 'tagname', 'packageid', 'status', 'store', 'leveloff_list'
        ];
        $return = array_filter($detail, function ($k) use ($fields) {
            return in_array($k, $fields);
        }, ARRAY_FILTER_USE_KEY);


        $bookService = new GroupBookingAppointService();
        //获取场次 + 价格日历
        $return['rules'] = $bookService->getRuleByGroupId($groupId);
        //获取套餐
        $return['combine'] = $bookService->getCombineByGroupId($groupId);
        //获取场次-套餐组合
        $return['rule_combine'] = $bookService->getRuleCombineByGroupId($groupId);
        return $return;
    }

    /**
     * 更新场次预约价格日历
     *
     * @param int $groupId
     * @param array $calendar
     * @return void
     * @author: 张涛
     * @date: 2021/05/12
     */
    public function updatePriceCalendar($groupId, $ruleId, $calendar)
    {
        if ($groupId < 1 || $ruleId < 1 || empty($calendar)) {
            throw new Exception(L_("参数有误"), 1001);
        }

        $ruleMod = new GroupBookingAppointRule();
        $ruleDetailMod = new GroupBookingAppointRuleDetail();

        $rule = $ruleMod->where('group_id', '=', $groupId)->where('rule_id', '=', $ruleId)->findOrEmpty();
        if ($rule->isEmpty()) {
            throw new Exception(L_("场次不存在"), 1003);
        }

        Db::startTrans();
        try {
            $dayList = array_column($calendar, 'day');
            $ruleDetailMod->where('rule_id', '=', $ruleId)->whereIn('day', $dayList)->delete();

            $priceCalendar = [];
            foreach ($calendar as $cal) {
                $priceCalendar[] = [
                    'rule_id' => $ruleId,
                    'day' => $cal['day'],
                    'price' => $cal['price'],
                    'is_sale' => $cal['is_sale']
                ];
            }
            $priceCalendar && $ruleDetailMod->insertAll($priceCalendar);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 根据场次ID获取价格日历
     *
     * @author: 张涛
     * @date: 2021/05/12
     */
    public function getPriceCalendarByRuleId($ruleId, $startDate, $endDate)
    {
        if ($ruleId < 1 || empty($startDate) || empty($endDate)) {
            throw new Exception(L_("参数有误"), 1001);
        }

        $ruleMod = new GroupBookingAppointRule();
        $ruleDetailMod = new GroupBookingAppointRuleDetail();

        $rule = $ruleMod->where('rule_id', $ruleId)->withoutField('is_del')->findOrEmpty();
        if ($rule->isEmpty()) {
            throw new Exception(L_("场次不存在"), 1003);
        }

        $where = [
            ['rule_id', '=', $ruleId],
            ['day', '>=', $startDate],
            ['rule_id', '<=', $endDate],
        ];
        $rule->price_calendar = $ruleDetailMod->where($where)->select()->toArray();
        return $rule->toArray();
    }

    /**
     * 新增/编辑课程预约
     *
     * @param array $param
     * @return void
     * @author: 汪晨
     * @date: 2021/04/30
     */
    public function courseAppoint($param)
    {
        if(empty($param['s_name'])){
            throw new \think\Exception("商品名称未填写", 1003);            
        }
        if(empty($param['old_price'])){
            throw new \think\Exception("原价未填写", 1003);            
        }
        if(empty($param['price'])){
            throw new \think\Exception("团购价未填写", 1003);            
        }
        if(empty($param['pic'])){
            throw new \think\Exception("图片未上传", 1003);            
        }
        if(empty($param['begin_time'])){
            throw new \think\Exception("团购开始时间未填写", 1003);            
        }
        if(empty($param['end_time'])){
            throw new \think\Exception("团购结束时间未填写", 1003);            
        }

        $param['group_id'] = empty($param['group_id']) ? 0 : $param['group_id'];

        $data = [
            'mer_id'                => $param['mer_id'],
            'group_cate'            => 'course_appoint',
            's_name'                => $param['s_name'],
            'label_group'           => empty($param['label_group']) ? 0 : $param['label_group'],
            'old_price'             => $param['old_price'],
            'price'                 => $param['price'],
            'is_invoice'            => empty($param['is_invoice']) ? 0 : $param['is_invoice'],
            'begin_time'            => strtotime($param['begin_time']),
            'end_time'              => strtotime($param['end_time']),
            'appoint_time_type'     => empty($param['appoint_time_type']) ? 0 : $param['appoint_time_type'],
            'appoint_time'          => empty($param['appoint_time']) ? 0 : $param['appoint_time'],
            'content'               => empty($param['content']) ? '' : $param['content'],
            'is_full_age'           => empty($param['is_full_age']) ? 0 : $param['is_full_age'],
            'age_start'             => empty($param['age_start']) ? 0 : $param['age_start'],
            'age_end'               => empty($param['age_end']) ? 0 : $param['age_end'],
            'study_person_start'    => empty($param['study_person_start']) ? 0 : $param['study_person_start'],
            'study_person_end'      => empty($param['study_person_end']) ? 0 : $param['study_person_end'],
            'course_nums'           => empty($param['course_nums']) ? 0 : $param['course_nums'],
            'status'                => empty($param['status']) ? 0 : $param['status'],
        ];
        $data = array_merge($data,Arr::only(input(),[
            'count_num',
            'once_max', 
            'once_max_day', 
            'once_min',
            'once_use_max', 
        ]));
        // 二级标签
        $data['label_ids'] = empty($param['label_ids']) ? '' : $data['label_ids'] = implode(',',$param['label_ids']);

        // 图片
        $pic = $param['pic'];
        if($pic){
            $data['pic'] = implode(';',$param['pic']);
        }else{
            $data['pic'] = '';
        }

        // 学习基础
        $study_basic = $param['study_basic'];
        if($study_basic){
            $data['study_basic'] = implode(',',$param['study_basic']);
        }else{
            $data['study_basic'] = '';
        }

        //  会员优惠
        $leveloff = $param['leveloff'] ?? '';
        if($leveloff){
            $tempLeveloff = [];
            foreach($leveloff as $level){
                $level['type'] = intval($level['type']);
                $level['vv'] = intval($level['vv']);
                if (($level['type'] > 0) && ($level['vv'] > 0)) {
                    $tempLeveloff[$level['level']] = $level;
                }
            }
            $data['leveloff'] = $tempLeveloff ? serialize($tempLeveloff) : '';
        }else{
            $data['leveloff'] = '';
        }

        // 店铺id
        $storeArr = $param['store_ids'] ?? []; 
        if (empty($storeArr)) {
            throw new \think\Exception(L_("请至少选择一家店铺"),1003);
        }
        $storeIds = array_column($storeArr,'store_id');
        $where = [['store_id' ,'in', implode(',',$storeIds )]];
        $storeList = (new MerchantStoreService())->getSome($where);
        if (empty($storeList)) {
            throw new \think\Exception(L_('您选择的店铺信息不正确！请重试。'));
        } else if (count($storeList) == 1) {
            $circleInfo = (new AreaService())->getAreaByAreaId($storeList[0]['circle_id']);
            if (empty($circleInfo)) {
                throw new \think\Exception(L_('您选择的店铺区域商圈信息不正确！请修改店铺资料后重试。'));
            }
            $data['prefix_title'] = $circleInfo['area_name'];
        } else {
            $data['prefix_title'] = count($storeList) . L_('店通用');
        }

        $res = $param['group_id'] ?? 0;
        if($param['group_id'] > 0){// 编辑
            $res = $this->groupModel->updateThis(['group_id'=>$res], $data);
        }
        else{// 新增
            $data['add_time'] = time();
            $res = $this->groupModel->add($data);
            $param['group_id'] = $res;
        }

        //绑定店铺
        $where = ['group_id' => $param['group_id']];

        // 查询原来的绑定
        $oldStoreList = (new StoreGroupService())->getSome($where);
        
        // 查询原来的店铺推荐
        $recommendList = array_column($oldStoreList,'is_rec','store_id');

        (new StoreGroupService())->del($where);
        $dataGroupStore = [];
        $packageList = array_column($param['store_ids'], 'package_id', 'store_id');
        foreach ($storeList as $key => $value) {
            $temp = [];
            $temp['group_id'] = $param['group_id'];
            $temp['store_id'] = $value['store_id'];
            $temp['province_id'] = $value['province_id'] ?? 0;
            $temp['city_id'] = $value['city_id'] ?? 0;
            $temp['area_id'] = $value['area_id'] ?? 0;
            $temp['circle_id'] = $value['circle_id'] ?? 0;
            $temp['package_id'] = $packageList[$value['store_id']] ?? 0;
            $temp['is_rec'] = isset($recommendList[$value['store_id']]) ? $recommendList[$value['store_id']] : 0;
            $dataGroupStore[] = $temp;
        }
        (new StoreGroupService())->addAll($dataGroupStore);

        return true;
    }

    /**
     * 课程预约详情
     *
     * @param int $groupId
     * @return void
     * @author: 汪晨
     * @date: 2021/04/30
     */
    public function getCourseAppointDetail($groupId)
    {
        if(empty($groupId['group_id'])) throw new \Exception("参数不正确");

        // 获取商品详情
        $group_info = $this->groupModel->getOne(['group_id' => $groupId['group_id']]);
        if(empty($group_info)){
            throw new \Exception("参数不正确");            
        }
        $group_info = $group_info->toArray();

        // 处理团购商品的公共信息
        $group_info = $this->dealCommonInfo($group_info);
        $group_info['label_ids'] = $group_info['label_ids'] ? $group_info['label_ids'] : '';

        // 处理课程预约商品的个性化字段
        $group_info['study_basic'] =  empty($group_info['study_basic']) ? [] : explode(',', $group_info['study_basic']);

        return $group_info;
    }


    /**
     * 用户端商品详情（普通商品和课程预约）
     *
     * @param int $groupId
     * @author: 衡婷妹
     * @date: 2021/05/10
     * @return array
     */
    public function getGoodsDetail($param)
    {
        $groupId = $param['group_id'] ?? 0;
        $nowGroupId = $param['now_group_id'] ?? 0;// 绑定套餐的商品，在选其他套餐是需要把当前商品id给我
        $nowGroupId = $nowGroupId ?: $groupId;
        
        $storeId = $param['store_id'] ?? 0;
        $gid = $param['gid'] ?? 0;
        $userLng = $param['user_lng'] ?? '';
        $userLat = $param['user_lat'] ?? '';
        $userOpenid = $param['openid'] ?? '';
        if(empty($groupId)) throw new \think\Exception("参数不正确",1001);

        // 用户信息
        $user = request()->user;
        $uid = $user['uid'] ?? 0;

        // 获取商品详情
        $groupInfo = $this->getOne(['group_id' => $groupId,'status'=>1]);
        if(empty($groupInfo)){
            throw new \think\Exception("商品不存在或已删除",1003);            
        }

        $returnArr = [];// 返回数组


        //判断是否收藏团购商品
        if ($uid < 1) {
            $returnArr['is_collect'] = false;
        } else {
            $collectRecord = (new MerchantUserRelationService())->getOne(['type' => 'group', 'group_id' => $groupId, 'uid' => $uid]);
            $returnArr['is_collect'] = !empty($collectRecord);
        }

        // 按钮
        // 验证团购状态
        $returnArr['is_begin'] = true;
        $returnArr['begin_time'] = date('m-d H:i',$groupInfo['begin_time']);
        if($groupInfo['begin_time'] > time()){// 未开始
            $returnArr['is_begin'] = false;
        }

        $returnArr['is_end'] = false;
        $returnArr['end_time'] = date('m-d H:i',$groupInfo['end_time']);
        if($groupInfo['end_time'] < time()){// 已结束
            $returnArr['is_end'] = true;
        }

        // 处理团购商品的公共信息
        $groupInfo = $this->dealCommonInfo($groupInfo);

        // 商家信息
        $merchant = (new MerchantService())->getMerchantByMerId($groupInfo['mer_id']);
        
        $returnArr['group_id'] = $groupInfo['group_id'];
        $returnArr['group_cate'] = $groupInfo['group_cate'];// 类型 
        $returnArr['mer_id'] = $groupInfo['mer_id']; // 归属商家ID
        $returnArr['s_name'] = $groupInfo['s_name'];// 商品名称
        $returnArr['merchant_name'] = $merchant['name'];// 商家名称
        $returnArr['sale_count'] = $groupInfo['sale_count']+($groupInfo['virtual_num']??0);// 销量+虚拟销量
        $returnArr['old_price'] = $groupInfo['old_price'];// 原价
        $returnArr['price'] = $groupInfo['price'];// 团购价 或 拼团价
        //如果是拼团显示团长优惠后价格
        if($groupInfo['pin_num'] >= 2 && $groupInfo['start_discount'] > 0){
            $returnArr['price'] = getFormatNumber($groupInfo['price'] * $groupInfo['start_discount'] /100);
        }
        $returnArr['cancel_type'] = $groupInfo['cancel_type'];// 0=不可取消 1=到期前几小时取消 2=随时可取消
        $returnArr['cancel_hours'] = intval($groupInfo['cancel_hours']);
        $returnArr['appoint_time'] = $groupInfo['appoint_time'];// 需提前预约时间：0=无需预约  >0时表示需提前多长时间预约
        $returnArr['image'] = $groupInfo['image'] ?? '';// 图片
        $returnArr['face_value'] =  $groupInfo['face_value'] ? get_format_number($groupInfo['face_value']) : '';// 代金券面值
        $returnArr['wechat_share_img'] = $groupInfo['wechat_share_img'] ?? '';// 小程序分享图片

        //单位
        $returnArr['type_unit'] = '个';
        if (($groupInfo['group_cate'] == 'normal' && $groupInfo['tuan_type'] != 2) || $groupInfo['group_cate'] == 'cashing') {
            $returnArr['type_unit'] = '张';
        }
        if ($groupInfo['group_cate'] == 'booking_appoint' || $groupInfo['group_cate'] == 'course_appoint') {
            $returnArr['type_unit'] = '次';
        }

        $returnArr['phone'] = '';// 用户手机号
        if($user){
            $returnArr['phone'] = phone_show($user['phone']);
        }

        // 套餐信息
        $returnArr['tagname'] = $groupInfo['tagname'];// 本团购套餐标签
        $returnArr['packageid'] = $groupInfo['packageid'];// 本团购套餐id
        $returnArr['package_list'] = [];// 套餐其他商品列表
        //套餐
        if ($groupInfo['packageid'] > 0) {
            $packageGroupList = $this->getSome(['packageid'=>$groupInfo['packageid'],'status'=>1], true, ['group_id'=>'DESC']);
            $thisPack = [];
            foreach($packageGroupList as $g){
                if($nowGroupId != $g['group_id']){
                    $returnArr['package_list'][] = [
                        'group_id' => $g['group_id'],
                        'name' => $g['name'],
                    ];
                }else{
                    $thisPack = [
                        'group_id' => $g['group_id'],
                        'name' => $g['name'],
                    ];
                }
            }
            $returnArr['package_list'] = array_merge([$thisPack],$returnArr['package_list']);
        }

        // 拼团信息
        $returnArr['pin_num'] = $groupInfo['pin_num']; // 拼团人数大于0表示开启拼团；等于0关闭拼团
        $returnArr['pin_effective_time'] = $groupInfo['pin_effective_time'];// 拼团有效期
        $returnArr['start_discount'] = $groupInfo['start_discount'];// 团长优惠政策
        $returnArr['start_max_num'] = $groupInfo['start_max_num'];// 团购每次最多可购买数量
        $returnArr['group_refund_fee'] = $groupInfo['group_refund_fee'];// 成团后退款手续费比例
        $returnArr['pin_list'] = [];
     	// 正在拼团列表
        if($returnArr['pin_num']){
            $returnArr['pin_list'] = (new GroupOrderService())->getPinLimitListByGroupGoods($groupInfo)['list'];
        }

		// 商品详情，需要处理下图片
        $returnArr['content'] = replace_file_domain_content_img(urldecode($groupInfo['content']));
        if (request()->param('app_version') > 0) { 
            //flutter兼容富文本表格样式有问题，app的富文本组件也没啥办法处理了，先特殊给个固定尺寸
            $tr = explode("/tr>", $returnArr['content']);
            $c = 0;
            foreach ($tr as $v) {
                $c = max(substr_count($v, "/td>"), $c);
            }
            if ($c > 0) {
                $returnArr['content'] .= "<style type='text/css'>td{width:" . (300 / $c) . "px}</style>";
            }
        }
		
        // 购买须知
        $returnArr['deadline_time'] = $groupInfo['deadline_time'];// 有效期（当effective_type=1时为天数,当effective_type=0时为日期）

        if($groupInfo['group_cate'] == 'course_appoint'){ // 课程预约结束时间
            $returnArr['deadline_time'] = $groupInfo['end_time'];
        }
        $returnArr['effective_type'] = $groupInfo['effective_type'];// 有效期类型 0=固定时间 1=领取多少天后失效 默认0
        $returnArr['appoint_time'] = $groupInfo['appoint_time'];// 需提前预约时间：0=无需预约  >0时表示需提前多长时间预约
        $returnArr['appoint_time_type'] = $groupInfo['appoint_time_type'];// 提前预约时间类型：0=天 1=小时
        $returnArr['once_max'] = $groupInfo['once_max'];// 一个ID最多购买数量
        $returnArr['once_max_day'] = $groupInfo['once_max_day'];// ID每天最多购买数量
        $returnArr['once_min'] = $groupInfo['once_min'];// 一个ID最少购买数量
        $returnArr['is_invoice'] = $groupInfo['is_invoice'];// 是否提供发票 1=提供 0=不提供

        // 店铺总数
        $where=[['g.group_id','=',$groupId],['s.status','=',1]];
        $store_list=(new GroupStore())->getStoreByGroup($where,'g.store_id');
        $returnArr['store']['count'] =  count($store_list) ?: '';

        // 店铺信息
        $store = [];
        $returnArr['appoint_gift']['name'] =  L_('预约礼');
        $returnArr['appoint_gift']['list'] =  [];
        if($storeId){
            $store = (new MerchantStoreService())->getStoreByStoreId($storeId);

        }

        if(empty($store)){// 获取距离最近的店铺,没有经纬度 获取最新的店铺
            $where = [];
            $where['group_ids'] = [$groupId];
            $where['lat'] = $userLat;
            $where['lng'] = $userLng;
            $list = (new StoreGroupService())->getStoreByGroup($where);
            $store = array_shift($list);
        }

        if(!$store||empty($store)||!$store['store_id']){
            throw new \think\Exception("店铺不存在",1003);
        }

        $returnArr['is_marketing_share'] = 0;//是否是当前商品所在店铺分销员
        if ($store['store_id'] && $store['open_store_marketing'] == 1) {
            $perId = (new StoreMarketingPerson())->where(['uid' => $uid])->value('id');
            if (!empty($perId)) {
                $stoData = (new StoreMarketingPersonStore())->where([
                    ['person_id', '=', $perId],
                    ['store_id', '=', $store['store_id']],
                    ['status', '=', 2],
                    ['is_del', '=', 0]
                ])->find();
                if (!empty($stoData)) {
                    $returnArr['is_marketing_share'] = 1;
                }
            }
        }

        $returnArr['store']['store_id'] = $store['store_id'] ?? 0;
        $returnArr['store']['name'] = $store['name'] ?? '';
        $returnArr['store']['address'] = $store['adress'] ?? '';
        $returnArr['store']['long'] = $store['long'] ?? '';
        $returnArr['store']['lat'] = $store['lat'] ?? '';
        if ($store['logo']) {
            $returnArr['store']['logo'] = replace_file_domain($store['logo']);
        } else {
            $pics = explode(';', $store['pic_info']);
            $returnArr['store']['logo'] = $pics ? replace_file_domain($pics[0]) : '';
        }
        $returnArr['store']['phone'] = isset($store['phone']) && $store['phone'] ? (is_array($store['phone']) ? $store['phone'] : explode(' ', $store['phone'])) : [];
        $returnArr['phone_list'] = isset($store['phone']) && $store['phone'] ? (is_array($store['phone']) ? $store['phone'] : explode(' ', $store['phone'])) : [];// 店铺电话

        // 预约礼
        $appointGift = (new GroupAppointService())->getStoreAppointGift($store['store_id']);
        $returnArr['appoint_gift']['list'] =  $appointGift ? explode(',', $appointGift['gift']) : [];
        // 评价数
        // 评分
        $where = [
            'o.group_id' => $groupId
        ];
        $returnArr['reply'] = [
            'count' => 0,// 评价总数
            'score' => 5,// 评价值
            'list' => [],
            'url' => cfg('site_url').get_base_url().'pages/store/replyList?store_id='.$store['store_id']//全部评价url
        ];
        $returnArr['reply']['list'] = (new ReplyService())->getListByGroupGoods($where,'r.*,u.nickname,u.avatar',['r.add_time'=>'desc'],1,2);
        if($returnArr['reply']['list']){
            $returnArr['reply']['count'] = (new ReplyService())->getCountByGroupGoods($where);// 评价总数
            $returnArr['reply']['score'] = (new ReplyService())->getScoreByGroupGoods($where);// 评价值
            if($uid){
                foreach($returnArr['reply']['list'] as $key => $val){
                    $can_reply = 0;
                    if($val['uid'] == $uid && is_null($val['user_reply']) && !is_null($val['merchant_reply'])){
                        $can_reply = 1;
                    }
                    $returnArr['reply']['list'][$key]['can_reply'] = $can_reply;
                }
            }
        }
        $returnArr['reply']['url'] .= '&total='.$returnArr['reply']['count'];


        //更多商家(显示距该用户直线距离最近的10家店铺)
        $cityId = $store['city_id'] ?? 0;
        $returnArr['more_store_list'] = (new MerchantStoreService())->getGroupRecommendStoreList('s.city_id='.$cityId, $userLng, $userLat);


        // 课程信息
        $returnArr['is_full_age'] = $groupInfo['is_full_age'] ?: 0;// 是否全龄段 1=是 0=否
        $returnArr['age_start'] = $groupInfo['age_start'] ?: 0;// 年龄起始
        $returnArr['age_end'] = $groupInfo['age_end'] ?: 0;// 年龄截止
        $studyBasicArr = '';
        if($groupInfo['study_basic']){
            $studyBasic = explode(',',$groupInfo['study_basic']);
            foreach($studyBasic as $k =>$v){
                if($k == 0){
                    if($v == 1){
                        $studyBasicArr = '零基础';
                    }elseif($v == 2){
                        $studyBasicArr = '初级';
                    }elseif($v == 3){
                        $studyBasicArr = '中级';
                    }elseif($v == 4){
                        $studyBasicArr = '高级';
                    }
                }else{
                    if($v == 1){
                        $studyBasicArr .= ',零基础';
                    }elseif($v == 2){
                        $studyBasicArr .= ',初级';
                    }elseif($v == 3){
                        $studyBasicArr .= ',中级';
                    }elseif($v == 4){
                        $studyBasicArr .= ',高级';
                    } 
                }
                // $studyBasicArr = $this->studyBasicArr[$groupInfo['study_basic']];// 学习基础
            }
        }
        $returnArr['study_basic_str'] = $studyBasicArr;// 学习基础
        $returnArr['study_person_start'] = $groupInfo['study_person_start'] ?: 0;// 上课人数起始值
        $returnArr['study_person_end'] = $groupInfo['study_person_end'] ?: 0;// 上课人数截止值
        $returnArr['course_nums'] = $groupInfo['course_nums'] ?: 0;// 课时节数
        $returnArr['price_range_low'] = $groupInfo['price_range_low'];// 课时节数
        $returnArr['price_range_height'] = $groupInfo['price_range_height'];// 课时节数
        // 规格
        $specificationsId = 0;
        if($groupInfo['trade_type']!='hotel' && $groupInfo['pin_num']<=0){
            // 获取最便宜的团购规格价格
            $where = [
                'group_id' => $groupInfo['group_id'],
                'status' => 1
            ];
            $specCheapInfo = (new GroupSpecificationsService())->getOne($where,true, ['price'=>'ASC']);
            if ($specCheapInfo) {
                $returnArr['price'] = $specCheapInfo['price'];
                $returnArr['old_price'] = $specCheapInfo['old_price'];
                $specificationsId = $specCheapInfo['specifications_id'];
            }
        } 

        // 酒店模块
        if($groupInfo['trade_type'] =='hotel'){
            // 酒店时间
            $depDate = $param['dep_time'] ?? '';
            $endDate = $param['end_time'] ?? '';
            if($depDate && strtotime(date('Y-m-d'))<strtotime($endDate)){
                $trade_hotel['time_dep_time'] = date('Y-m-d',strtotime($depDate));
                $trade_hotel['time_end_time'] = date('Y-m-d',strtotime($endDate));
                $trade_hotel['show_dep_time'] = date('m-d',strtotime($depDate));
                $trade_hotel['show_end_time'] =  date('m-d',strtotime($endDate));
                $trade_hotel['dep_time'] =  $_COOKIE['dep_time'];
                $trade_hotel['end_time'] =  $_COOKIE['end_time'];
                $trade_hotel['days'] =  (strtotime($trade_hotel['end_time'])-strtotime( $trade_hotel['dep_time']))/86400;
            }else{
                $trade_hotel['time_dep_time'] = date('Y-m-d');
                $trade_hotel['show_dep_time'] = date('m-d');
                $trade_hotel['dep_time'] = date('Ymd');
                $trade_hotel['time_end_time'] = date('Y-m-d',time()+86400);
                $trade_hotel['show_end_time'] = date('m-d',time()+86400);
                $trade_hotel['end_time'] = date('Ymd',time()+86400);
                $trade_hotel['days'] =   (strtotime($trade_hotel['end_time'])-strtotime( $trade_hotel['dep_time']))/86400;
            }
           
            if(!$depDate && !$endDate){
                $param['dep_time'] = date('Ymd');
                $param['end_time'] = date('Ymd',strtotime('+1 day'));
            }

            // 酒店列表
            $trade_hotel['hotel_list'] = (new TradeHotelCategoryService)->getTradeHotelStock($param);
            $returnArr['trade_hotel'] = $trade_hotel;
        }

        //店铺分销员分销商品
        $share_code = $param['share_code'] ?? '';//分享码，业务员分享商品购买时必传
        $returnArr['share_id'] = 0;
        $returnArr['is_marketing_goods'] = 0;
        $setpriceData = [];
        if(!empty($share_code)){
            $shareData = (new StoreMarketingShareLog())->where([['share_code', '=', $share_code]])->find();
            if (empty($shareData)) {
                throw new \think\Exception("分享码有误",1003);
            }
            $shareData=$shareData->toArray();
            if ($shareData['create_time'] > time() - 86400) {//分享链接24小时之内有效
                $setpriceData = (new StoreMarketingPersonSetprice())->where([
                    ['share_id', '=', $shareData['id']],
                    ['person_id', '=', $shareData['person_id']],
                    ['goods_type', '=', 1],
                    ['goods_id', '=', $groupInfo['group_id']]
                ])->select();
                if ($setpriceData) {
                    $setpriceData = $setpriceData->toArray();
                    $setpriceData = $setpriceData ? array_column($setpriceData, null, 'specs_id') : [];
                }
//                $setpriceData = (new StoreMarketingPersonSetprice())->where([
//                    ['share_id', '=', $shareData['id']],
//                    ['person_id', '=', $shareData['person_id']],
//                    ['goods_type', '=', 1],
//                    ['goods_id', '=', $groupInfo['group_id']],
//                    ['specs_id', '=', $specificationsId]
//                ])->find();
                if (!empty($setpriceData[$specificationsId])) {
                    $returnArr['price'] = $setpriceData[$specificationsId]['price'];
                }
                $returnArr['is_marketing_goods'] = 1;
                $returnArr['share_id'] = $shareData['id'];
            }
        }

        //拼团团购详情页面 参团价格分割
		if($groupInfo['pin_num']>0) {
            $returnArr['gid'] = $gid;
			$groupStartService = new GroupStartService();

            // 分享拼团
			$nowStart = $groupStartService->getOne(['id'=>$gid]);

			if ($nowStart && !$nowStart['status'] && !empty($gid)) {
				$inGroup = false;// 是否已参团
				$start_user_arr = $groupStartService->getBuyererByOrderId('',$gid);

				foreach($start_user_arr as $st){
					if($st['uid'] == $uid){
						$inGroup = true;
					}
				}
				if(!$inGroup){
                    // 查询最新拼团信息
                    if ($nowStart && $nowStart['complete_num'] <= $nowStart['num']) {
                        $nowStart['can_buy'] = false;
                        $nowStart['error_msg'] = L_('当前X1已成团，请参加其他拼团或开团！',cfg('group_alias_name'));
                    }
					$endTime = $nowStart['start_time'] + $groupInfo['pin_effective_time'] * 3600;// 拼团结束时间
                    // 查看当前拼团是否过期
					$effectiveTime = $endTime - time();
					if ($effectiveTime > 0) {// 未过期
						$nowStart['end_time'] = date('Y-m-d H:i:s',$endTime);
                        $nowStart['surplus_time'] = $endTime-time();// 距离结束的时间戳
                        $returnArr['pin_group'] = $nowStart;
					} else {
						$groupStartService->updateThis(['id'=>$gid], ['status'=>2]); //2 团购小组超时
                        $returnArr['pin_group'] = (object)[];
					}
				}
			}
		}

        // 查询一下团购规格
        $where = [
            'group_id' => $groupInfo['group_id'],
            'status' => 1
        ];
        $groupSpecifications = (new GroupSpecificationsService())->getSome($where,true, ['price'=>'ASC']);
        
        $returnArr['spec_list'] = $groupSpecifications ?: [];
        $returnArr['is_set_price']=0;
        if ($groupSpecifications) {
            $info = array();
            foreach ($groupSpecifications as &$val) {
                if($val['price_range_low']>0 || $val['price_range_height']>0){
                    $returnArr['is_set_price']=1;
                }
                // 计算一下是否当前客户能够购买该产品
                $uid = $user['uid'] ?? 0;
                //每ID每天限购
                $today_can_buy = (new GroupOrderService())->getSpecBuyNum($val['group_id'], $val, $uid);

                $val['today_can_buy'] = $today_can_buy['num'];
                $val['num_desc'] = $today_can_buy['desc'] ?? '';
                $info[$val['specifications_id']] = $val;
                $returnArr['sale_count'] += $val['sale_count'];
                if ($returnArr['is_marketing_goods'] == 1 && !empty($setpriceData[$val['specifications_id']])) {
                    $val['price'] = $setpriceData[$val['specifications_id']]['price'];
                }
                //消费一元获得积分
                $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($val['price'],'group',false,$groupInfo['score_percent']);
                $val['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
                $val['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
            }
        }

       if(empty($groupSpecifications)){
           if($returnArr['price_range_low']>0 || $returnArr['price_range_height']>0){
               $returnArr['is_set_price']=1;
           }
       }
       
        $returnArr['spec_list'] = $groupSpecifications ?: [];

        // 客服地址
        if (true) {
            $kf = (new MerchantStoreKefuService())->getOne(['store_id' => $store['store_id']]);
            $uid = $user['uid'] ?? 0;
            if ($kf && $uid > 0 && $store['store_id'] > 0) {
                $kfUrl = build_im_chat_url('user_' . $uid, $kf['username'], 'user2store', ['from' => 'group']);
            }
        } else {
            $services = (new CustomerServiceService())->getSome(['mer_id'=>$groupInfo['mer_id']]);
            if ($userOpenid && $services) {
                $key = get_encrypt_key(array('app_id' => cfg('im_appid'), 'openid' => $userOpenid),cfg('im_appkey'));
                $kfUrl = (cfg('im_url') ? cfg('im_url') : 'http://im-link.weihubao.com') . '/?app_id=' .cfg('im_appid') . '&openid=' . $groupInfo . '&key=' . $key . '#serviceList_' . $groupInfo['mer_id'];

            }
        }
        $returnArr['kefu_url'] = $kfUrl ?? '';

        //店铺绑定餐饮套餐
        $returnArr['package_detail_list'] = (object)[];
        if (!empty($store)) {
            $where = [];
            $where['group_id'] = $groupId;
            $where['store_id'] = $store['store_id'];
            $store = (new StoreGroupService())->getOne($where);
            // 查询套餐是否存在
            $packageInfo = [];
            isset($store['package_id']) && $packageInfo = (new FoodshopGoodsPackageService())->getOne(['id' => $store['package_id'],'status'=>1,'is_order'=>1]);

            if (!empty($packageInfo)) {
                // 套餐详情
                $packageList= (new FoodshopGoodsPackageDetailService())->getPackageDetailByPid(['pid'=>$store['package_id']]);
                $returnArr['package_detail_list'] = $packageList;
            }
        }

        //商品是否能购买
        $returnArr['can_buy'] = true;
        $returnArr['can_not_buy_txt'] = [];
        if (!$returnArr['is_begin']) {
            $returnArr['can_buy'] = false;
            $returnArr['can_not_buy_txt'] = [$returnArr['begin_time'], L_('开始抢购')];
        } else if ($returnArr['is_end']) {
            $returnArr['can_buy'] = false;
            $returnArr['can_not_buy_txt'] = [L_('该X1已结束', ['X1' => cfg('group_alias_name')])];
        } else if (empty($returnArr['spec_list']) && $groupInfo['count_num'] && $groupInfo['sale_count'] >= $groupInfo['count_num']) {
            $returnArr['can_buy'] = false;
            $returnArr['can_not_buy_txt'] = [L_('已售罄')];
        } else if (empty($returnArr['spec_list']) && $uid > 0) {
            //判断ID限购，每天限购
            $groupOrderService = new GroupOrderService();
            $todayBuyCount = $groupOrderService->getOnceMaxDayBuyNumber($groupId, $uid, 0, true);
            $totalBuyCount = $groupOrderService->getOnceMaxDayBuyNumber($groupId, $uid, 0, false);
            if (($groupInfo['once_max'] > 0 && $totalBuyCount >= $groupInfo['once_max']) || ($groupInfo['once_max_day'] > 0 && $todayBuyCount >= $groupInfo['once_max_day'])) {
                $returnArr['can_buy'] = false;
                $returnArr['can_not_buy_txt'] = [L_('超出限购'), L_('不可购买')];
            }
        }

        //分享文案
        $shareWx = (cfg('pay_wxapp_important') && cfg('pay_wxapp_username')) ? 'wxapp' : 'h5';
        $shareUrl = get_base_url('pages/group/v1/groupDetail/index', true) . '?group_id=' . $groupId . '&gid=' . $gid . '&store_id=' . $storeId;
        $shareImage = $returnArr['wechat_share_img']?:($returnArr['image'][0] ?? '');
        $shareTitle = $groupInfo['s_name'] ?? '';
        $shareDesc = $groupInfo['name'] ?? '';
        $returnArr['share_wx'] = $shareWx;
        if ($shareWx == 'wxapp') {
            $returnArr['share_info'] = [
                'user_name' => cfg('pay_wxapp_username'),
                'path' => '/pages/plat_menu/index?redirect=webview&webview_url=' . urlencode($shareUrl) . '&webview_title=' . urlencode($shareTitle),
                'img' => $shareImage,
                'desc' => $shareDesc,
                'title' => $shareTitle,
				'url' => $shareUrl
            ];
        } else {
            $returnArr['share_info'] = [
                'title' => $shareTitle,
                'desc' => $shareDesc,
                'img' => $shareImage,
                'url' => $shareUrl
            ];
        }
        $count=(new GroupStore())->getCount(['group_id'=>$groupId]);
        $returnArr['style']="normal";
        //积分展示
        $returnArr['score_text'] = '';
        if(customization('goods_show_score') && $scorePrice = floatval($returnArr['price'])){
            $scorePercent = PercentRateService::scorePercent('group', $groupId, $returnArr['mer_id']);
            $score = bcmul($scorePrice, $scorePercent, 2);
            if(cfg('score_round_two')){
                $score = sprintf('%.2f',floatval($score));
            }else{
                $score = round($score);
            }
            floatval($score) > 0 && $returnArr['score_text'] = cfg('score_name').'+'.$score;
        }
        if($count>1){
            $returnArr['style']="not_normal";
        }

        //消费一元获得积分
        $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($returnArr['price'],'group',($groupSpecifications?true:false),$groupInfo['score_percent']);
        $returnArr['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
        $returnArr['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
        return $returnArr;
    }

    /**
     * 将库存写入redis队列
     * @param array $group
     * @return string
     * @author: 衡婷妹
     * @date_time:  2020/11/25 14:38
     */
    public function addRedis($groupId)
    {
        if(empty(config('redis.connections.host'))){
            return true;
        }

        try {
            $redis = new Redis();
            
        } catch (\Exception $e) {
            return false;
        }
        $group = $this->getOne(['group_id'=>$groupId]);
        $key = 'group_'.$groupId;
        $goodsCount = 0;
        if($group['stock_reduce_method'] == 0){// 支付后减库存 减去未支付的商品数量才是商品当前能购买的数量
            // 查询为未支付的订单
            $where = [
                'group_id' => $groupId,
                'status' => 0,
                'paid' => 0
            ];
            $goodsCount = (new GroupOrderService())->getGoodsNum($where);
        }

        if($group['count_num'] > 0){
            // 限库存
            $num = max($group['count_num']-$group['sale_count']-$goodsCount,0);// 剩余库存
            if($num == 0){// 没有库存
                $redis->delete($key);
            }else{// 有库存
                $len =  $redis->llen($key);// 查询已有的长度
                $count = $num - $len;// 还需要插入移除redis队列的长度
                if($count < 0){// 减库存 redis长度大于当前剩余库存 移除多余的
                    for($i=0; $i<abs($count); $i++){
                        $redis->lpop($key,1);
                    }
                } else {  // 加库存  
                    for($i=0; $i<$count; $i++){
                        $redis->lpush($key,1);
                    }
                }   
            } 
        }else{ // 不限库存
            $redis->delete($key);
        }

        // 查询商品规格库存
        $groupSpecificationsList = (new  GroupSpecificationsService())->getSome(['group_id'=>$groupId,'status'=>1]);
        if($groupSpecificationsList){
            foreach($groupSpecificationsList as $_spec){
                $where = [
                    ['group_id', '=', $groupId],
                    ['specifications_id', '=', $_spec['specifications_id']],
                    ['status', '=', 0],
                    ['paid', '=', 0],
                ];
                
                $count = (new GroupOrderService())->getSum($where);

                $key = 'group_'.$groupId.'-s_'.$_spec['specifications_id'];
                if($_spec['count_num'] > 0){
                    // 限库存
                    $num = max($_spec['count_num']-$_spec['sale_count']-$count,0);// 剩余库存
                    if($num == 0){// 没有库存
                        $redis->delete($key);
                    }else{// 有库存
                        $len =  $redis->llen($key);// 查询已有的长度
                        $count = $num - $len;// 还需要插入移除redis队列的长度
                        if($count < 0){// 减库存 redis长度大于当前剩余库存 移除多余的
                            for($i=0; $i<abs($count); $i++){
                                $redis->lpop($key,1);
                            }
                        } else {  // 加库存  
                            for($i=0; $i<$count; $i++){
                                $redis->lpush($key,1);
                            }
                        }   
                    } 
                }else{ // 不限库存
                    $redis->delete($key);
                }
        
            }
        }

        // 查询商品场次预约
        if($group['group_cate'] == 'booking_appoint'){
            $combineList = (new  GroupBookingAppointService())->getRuleCombineByGroupId($groupId);
            if($combineList){// 有套餐的
                foreach($combineList as $_com){
                    $where = [
                        ['o.group_id', '=', $groupId],
                        ['o.status', '=', 0],
                        ['o.paid', '=', 0],
                        ['b.rule_id', '=', $_com['rule_id']],
                        ['b.combine_id', '=', $_com['id']],
                    ];
                    $count = (new GroupOrderService())->getBookingAppointSum($where);

                    $key = 'group_'.$groupId.'-r_'.$_com['rule_id'].'-c_'.$_com['id'];
                    if($_com['stock_num'] > 0){
                        // 限库存
                        $num = max($_com['stock_num']-$_com['sale_count']-$count,0);// 剩余库存
                        if($num == 0){// 没有库存
                            $redis->delete($key);
                        }else{// 有库存
                            $len =  $redis->llen($key);// 查询已有的长度
                            $count = $num - $len;// 还需要插入移除redis队列的长度
                            if($count < 0){// 减库存 redis长度大于当前剩余库存 移除多余的
                                for($i=0; $i<abs($count); $i++){
                                    $redis->lpop($key,1);
                                }
                            } else {  // 加库存  
                                for($i=0; $i<$count; $i++){
                                    $redis->lpush($key,1);
                                }
                            }   
                        } 
                    }else{ // 不限库存
                        $redis->delete($key);
                    }
            
                }
            }

            $ruleList = (new  GroupBookingAppointService())->getRuleByGroupId($groupId);
            if($ruleList){// 无套餐的
                foreach($ruleList as $_com){
                    $where = [
                        ['o.group_id', '=', $groupId],
                        ['o.status', '=', 0],
                        ['o.paid', '=', 0],
                        ['b.rule_id', '=', $_com['rule_id']],
                        ['b.combine_id', '=', 0],
                    ];
                    $count = (new GroupOrderService())->getBookingAppointSum($where);
                    $key = 'group_'.$groupId.'-r_'.$_com['rule_id'].'-c_0';
                    if($_com['count'] > 0){
                        // 限库存
                        $num = max($_com['count']-$_com['sale_count']-$goodsCount,0);// 剩余库存
                        if($num == 0){// 没有库存
                            $redis->delete($key);
                        }else{// 有库存
                            $len =  $redis->llen($key);// 查询已有的长度
                            $count = $num - $len;// 还需要插入移除redis队列的长度
                            if($count < 0){// 减库存 redis长度大于当前剩余库存 移除多余的
                                for($i=0; $i<abs($count); $i++){
                                    $redis->lpop($key,1);
                                }
                            } else {  // 加库存  
                                for($i=0; $i<$count; $i++){
                                    $redis->lpush($key,1);
                                }
                            }   
                        } 
                    }else{ // 不限库存
                        $redis->delete($key);
                    }
            
                }
            }
                
            

        }

        return true;
    }

    /**
     * 验证库存
     *
     * @param int $groupId
     * @param int $num 购买数量
     * @param int $type 1-团购商品 2-规格 3-场次套餐
     * @param array $group 团购商品详情
     * @param array $specificationsInfo 规格详情
     * @param array $combine 场次套餐详情
     * @return void
     * @author: 衡婷妹
     * @date: 2021/05/27
     */
    public function checkRedisStock($groupId, $num, $type = 1, $group = [], $specificationsInfo = [], $combine = [])
    {
        // 团购详情
        if(empty($group)){
            $group = $this->getOne(['group_id'=>$groupId]);
        }

        if(empty(config('redis.connections.host'))){
            return true;
        }
        try {
            $redis = new Redis();
        } catch (\Exception $e) {// 没有安装redis的走mysql验证库存
            $res = $this->checkStock($groupId, $num, $type, $group, $specificationsInfo, $combine);
            return $res;
        }

        switch($type){
            case 1:// 团购商品
                if (intval($group['count_num']) == 0) {// 不限库存
                   return true;
                }
                $key = 'group_'.$groupId;
                break;
            case 2:// 规格
                if (intval($specificationsInfo['count_num']) == 0) {// 不限库存
                    return true;
                }
                $key = 'group_'.$groupId.'-s_'.$specificationsInfo['specifications_id'];
                break;
            case 3://场次套餐
                if (intval($combine['stock_num']) == 0) {// 不限库存
                    return true;
                }
                $key = 'group_'.$groupId.'-r_'.$combine['rule_id'].'-c_'.$combine['id'];
                break;
        }
        
        try {
            //监视一个(或多个)key，如果在事务执行之前这个(或这些) key 被其他命令所改动，那么事务将被打断
            // $redis->watch(array($key));
        
            $redis->multi();
            $redis->lRange($key,0,$num-1);

            $redis->ltrim($key,$num,-1);
            //执行事务块内的所有命令
            $status = $redis->exec();

            //失败则取消事务
            if (count($status[0]) < $num) {
                $redis->discard(); 
                for($i=0; $i<count($status[0]); $i++){
                    $redis->lpush($key,1);
                }
                $len = $redis->lLen($key);
                if($len > 0){
                    throw new \think\Exception(L_('此单当前最多还可购买X1份',$len),1003);
                }else{
                    throw new \think\Exception(L_('当前团购已经售罄！'),1003);
                }
            }
        } catch (\Exception $e){
            throw new \think\Exception($e->getMessage(),1005);
        }

        return true;
    }

    /**
     * 验证库存非redis
     *
     * @param int $groupId
     * @param int $num 购买数量
     * @param int $type 1-团购商品 2-规格 3-场次套餐
     * @param array $group 团购商品详情
     * @param array $specificationsInfo 规格详情
     * @param array $combine 场次套餐详情
     * @return void
     * @author: 衡婷妹
     * @date: 2021/10/27
     */
    public function checkStock($groupId, $num, $type = 1, $group = [], $specificationsInfo = [], $combine = [])
    {
        // 团购详情
        if(empty($group)){
            $group = $this->getOne(['group_id'=>$groupId]);
        }

        $unpaidGoodsCount = 0; // 未支付的商品数量

        switch($type){
            case 1 :// 团购商品
                if($group['count_num'] == 0){// 不限库存
                    return true;
                }
                if($group['stock_reduce_method'] == 0){// 支付后减库存 减去未支付的商品数量才是商品当前能购买的数量
                    // 查询为未支付的订单
                    $where = [
                        'group_id' => $groupId,
                        'status' => 0,
                        'paid' => 0
                    ];
                    $unpaidGoodsCount = (new GroupOrderService())->getGoodsNum($where);
                }
                
                $stockNum = max($group['count_num']-$group['sale_count']-$unpaidGoodsCount,0);// 剩余库存
                break;

            case 2 :// 规格
                if($specificationsInfo['count_num'] == 0){// 不限库存
                    return true;
                }

                if($group['stock_reduce_method'] == 0){// 支付后减库存 减去未支付的商品数量才是商品当前能购买的数量
                    $where = [
                        ['group_id', '=', $groupId],
                        ['specifications_id', '=', $specificationsInfo['specifications_id']],
                        ['status', '=', 0],
                        ['paid', '=', 0],
                    ];
                    
                    $unpaidGoodsCount = (new GroupOrderService())->getSum($where);
                }
                
                $stockNum = max($specificationsInfo['count_num']-$specificationsInfo['sale_count']-$unpaidGoodsCount,0);// 剩余库存
                
                break;
            case 3 :// 场次套餐
                if($combine['stock_num'] == 0){// 套餐
                    return true;
                }

                if($group['stock_reduce_method'] == 0){// 支付后减库存 减去未支付的商品数量才是商品当前能购买的数量
                    $where = [
                        ['o.group_id', '=', $groupId],
                        ['o.status', '=', 0],
                        ['o.paid', '=', 0],
                        ['b.rule_id', '=', $combine['rule_id']],
                        ['b.combine_id', '=', $combine['id']],
                    ];
                    $unpaidGoodsCount = (new GroupOrderService())->getBookingAppointSum($where);
                }

                $stockNum = max($combine['stock_num']-$combine['sale_count']-$unpaidGoodsCount,0);// 剩余库存
                break;
        }
           
        if($stockNum <= 0){
            throw new \think\Exception(L_('当前团购已经售罄！'),1003);
        }elseif($stockNum < $num){
            throw new \think\Exception(L_('此单当前最多还可购买X1份',$stockNum),1003);
        }
        return true;
    }

     /**
     * 增加redis
     *
     * @param int $groupId
     * @param array $specificationsInfo 规格信息
     * @return void
     * @author: 衡婷妹
     * @date: 2021/05/11
     */
    public function addRedisStock($groupId , $num)
    {
        if(empty(config('redis.connections.host'))){
            return true;
        }
        try {
            $redis = new Redis();
        } catch (\Exception $e) {
            return false;
        }

        $key = 'group_'.$groupId;
        for($i=0; $i<$num; $i++){
            $redis->lpush($key,1);
        }
        return true;
    }

   //得到店铺下的团购
    public function platRecommendGroupList($where,$field,$order){
        return $this->groupModel->platRecommendGroupList($where,$field,$order);
    }

    /**
     * 获取店铺进行中的代金券商品
     *
     * @param int $storeId
     * @return void
     * @author: 张涛
     * @date: 2021/05/11
     */
    public function getCashingGoodsByStoreId($storeId)
    {
        $tm = time();
        $rs = [];
        $where = [
            ['s.store_id', '=', $storeId],
            ['g.status', '=', 1],
            ['g.group_cate', '=', 'cashing'],
            ['g.begin_time', '<', $tm],
            ['g.end_time', '>', $tm],
        ];
        $goods = $this->groupModel->alias('g')
            ->join('group_store s', 's.group_id = g.group_id')
            ->where($where)
            ->order('g.group_id', 'DESC')
            ->select();
        if (!$goods->isEmpty()) {
            foreach ($goods as $g) {
                $item = [
                    'group_id' => $g->group_id,
                    'name' => L_('X1代金券', ['X1' => get_format_number($g->face_value) . cfg('Currency_txt')]),
                    'sale_count' => $g->sale_count,
                    'price' => get_format_number($g->price),
                    'old_price' => get_format_number($g->old_price),
                ];
                $item['desc'][] = $this->groupModel->getIsGeneralDesc($g->is_general);
                if ($g->once_use_max > 0) {
                    $item['desc'][] = L_('单次可用X1张', ['X1' => $g->once_use_max]);
                }
                if ($g->appoint_time == 0) {
                    $item['desc'][] = L_('免预约');
                }
                $rs[] = $item;
            }
        }
        return $rs;
    }

    /**
     * 获取店铺团购团购
     *
     * @param int $storeId
     * @return void
     * @author: 张涛
     * @date: 2021/05/11
     */
    public function getGoodsByStoreId($storeId, $groupCate = '', $isRecommend = false, $rawWhere = '', $limit = 0, $page = 0,$source='other')
    {
        $tm = time();
        $rs = [];
        $where = [
            ['s.store_id', '=', $storeId],
            ['g.status', '=', 1],
            ['g.begin_time', '<', $tm],
            ['g.end_time', '>', $tm],
        ];
        if ($groupCate) {
            $where[] = ['g.group_cate', '=', $groupCate];
        }
        if ($isRecommend) {
            $where[] = ['s.is_rec', '=', 1];
        }

        $isPage =  ($page > 0 && $limit > 0);

        if ($isPage) {
            //有分页返回总条数
            $count = $this->groupModel->alias('g')
                ->join('group_store s', 's.group_id = g.group_id')
                ->where($where)
                ->count();
            $mod = $this->groupModel->alias('g')
                ->join('group_store s', 's.group_id = g.group_id')
                ->where($where)
                ->order('g.group_id', 'DESC')
                ->page($page, $limit);
        } else {
            $mod = $this->groupModel->alias('g')
                ->join('group_store s', 's.group_id = g.group_id')
                ->where($where)
                ->order('g.group_id', 'DESC')
                ->limit($limit);
        }

        $rawWhere && $mod->whereRaw($rawWhere);
        $goods = $mod->select();
        if (!$goods->isEmpty()) {
            $groupImageService = new GroupImageService();
            foreach ($goods as $g) {
                $picArr = array_filter(explode(';', $g->pic));
                $mainImage = isset($picArr[0]) ? $groupImageService->getImageByPath($picArr[0], 0) : '';
                //消费一元获得积分
                $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($g['price'],'group',false,$g['score_percent']);
                $item = [
                    'group_id' => $g->group_id,
                    'img' => $mainImage ? thumb_img($mainImage, 450, 250, 'fill') : '',
                    's_name' => $g->s_name,
                    'name' => $g->name,
                    'intro' => $g->intro,
                    'is_rec' => $g->is_rec,
                    'sale_count' => $this->getSaleCount($g),
                    'price' => get_format_number($g->price),
                    'old_price' => get_format_number($g->old_price),
                    'buy_can_get_score' => $buy_can_get_score?$buy_can_get_score['score']:0,
                    'buy_can_get_score_txt' => $buy_can_get_score?$buy_can_get_score['score_txt']:0,
                ];

                if($source!='index'){
                    // 规格属性
                    // 获得价格最低的规格金额
                    $where = [
                        'group_id' => $g->group_id,
                        'status' => 1
                    ];
                    $spec= (new GroupSpecificationsService())->getOne($where,'price',['price' => 'ASC']);
                    if($spec){
                        $item['price'] = get_format_number($spec['price']);

                        $spec= (new GroupSpecificationsService())->getOne($where,'price',['price' => 'ASC']);
                    }
                }
                $rs[] = $item;
            }
        }
        if ($isPage) {
            return ['count' => $count, 'total_page' => ceil($count / $limit), 'lists' => $rs];
        } else {
            return $rs;
        }
    }
    /**
     * 获得商品已售数量
     *
     * @param array $group
     * @return int
     * @author: 衡婷妹
     * @date: 2021/08/07
     */
    public function getSaleCount($group)
    {
        $saleCount = $group['sale_count'] ?? 0;
        $where = [
            'group_id' => $group['group_id'],
            'status' => 1
        ];
        $specCount = (new GroupSpecificationsService())->getFieldSum($where);

        $saleCount += $specCount;

        return intval($saleCount);
    }


    /**
     * 获取店铺综合主页进行中的拼单数据
     *
     * @param int $storeId
     * @return void
     * @author: 张涛
     * @date: 2021/05/11
     */
    public function getCurrentPinRecordByStoreId($storeId)
    {
        $startService = new GroupStartService();

        $total = $startService->getNormalGoodsCountInProgress($storeId);
        //获取拼团人数最少，成团时间最少的一条记录
        if ($total > 0) {
            $info = $startService->getShortestPinTime($storeId);
        } else {
            $info = [];
        }
        return ['total' => $total, 'info' => $info];
    }


    /**
     * 获取店铺场次预约
     *
     * @param int $storeId
     * @return void
     * @date: 2021/05/12
     */
    public function getBookingAppointByStoreId($storeId, $days = 7)
    {
        $tm = time();
        $week = ['0' => L_('周日'), 1 => L_('周一'), 2 => L_('周二'), 3 => L_('周三'), 4 => L_('周四'), 5 => L_('周五'), 6 => L_('周六')];

        $dateList = [];
        for ($d = 0; $d < $days; $d++) {
            $unix = $tm + $d * 86400;
            $w = date('w', $unix);
            if ($d == 0) {
                $wName = L_('今天');
            } else if ($d == 1) {
                $wName = L_('明天');
            } else {
                $wName = $week[$w] ?? '';
            }
            $dateList[] = [
                'date' => date('m-d', $unix),
                'ymd_date' => date('Y-m-d', $unix),
                'week' => $wName
            ];
        }

        $where = [
            ['s.store_id', '=', $storeId],
            ['g.status', '=', 1],
            ['g.group_cate', '=', 'booking_appoint'],
        ];
        $goods = $this->groupModel->alias('g')
            ->join('group_store s', 's.group_id = g.group_id')
            ->field('g.group_id,g.s_name')
            ->where($where)
            ->order('g.group_id', 'DESC')
            ->select()
            ->toArray();

        $appointService = new GroupBookingAppointService();

        //获取一天的场次预约
        $bookingAppoint = [];
        foreach ($goods as $gv) {
            $ruleInfo = $appointService->getRuleInfo($gv['group_id'], date('Y-m-d', $tm));
            if (empty($ruleInfo)) {
                continue;
            }
            $gv['rule_info'] = $ruleInfo;
            $bookingAppoint[] = $gv;
        }

        //获取预订总人数
        $saleNum = (new GroupOrderService())->getBookingAppointCount($storeId);
        if(empty($bookingAppoint)){
            $dateList = [];
        }
        return ['sale_num' => $saleNum, 'calendar' => $dateList, 'data' => $bookingAppoint];
    }


    /**获取团购订单列表
     * @param $param
     * @return array
     */
    public function getGoodsOrderList($param)
    {
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 10;
        $start = ($page - 1) * $pageSize;
        $page = $param['page'] ?? 1;
        // 返回数据
        $returnArr = [];
        // 查询条件
        $where = [];
        // 排序
        $order = [
            'is_running' => 'DESC',
            'status' => 'DESC',
            'group_id' => 'DESC',
        ];
        
        if(isset($param['mer_id']) && $param['mer_id']){// 商家id
            $where[] = ['mer_id', '=', $param['mer_id']];
        }

        if(isset($param['keyword']) && $param['keyword']){// 关键字查询
            $keyword = addslashes($param['keyword']);
            $where[] = ['s_name', 'exp', Db::raw('like "%' . $keyword . '%" OR name like "%'.$keyword.'%"')];
        }  
        
        if(isset($param['status']) && $param['status'] != -1){// 团购状态
            $where[] = ['status', '=', $param['status']];
        }

        if(isset($param['is_running']) && $param['is_running'] != -1){// 运行状态
            if($param['is_running'] == 1){
                $where[] = ['end_time', '>', time()];
            }else{
                $where[] = ['end_time', '<', time()];
            }
        }

        $field = '*,end_time-'.time().' as is_running';
        $list = $this->getSome($where,$field,$order,$start,$pageSize);
        $total = $this->getCount($where);
        foreach($list as &$_goods){
            $_goods['group_cate_name'] = isset($this->groupCateNameArr[$_goods['group_cate']]) ? $this->groupCateNameArr[$_goods['group_cate']] : '';// 团购类型
            $_goods['status_str'] = $this->statusArr[$_goods['status']];// 团购状态
            // 运行状态
            if($_goods['end_time'] >= time()){
                $_goods['is_running'] = 1;
                $_goods['is_running_str'] = L_('运行中');
            }else{
                $_goods['is_running'] = 0;
                $_goods['is_running_str'] = L_('已结束');
            }
            // 评论数
            $where = [
                'o.group_id' => $_goods['group_id']
            ];
            $c['reply_count'] = (new ReplyService())->getCountByGroupGoods($where);// 评价总数
            $_goods['begin_time'] = date('Y-m-d H:i:s',$_goods['begin_time']);
            $_goods['end_time'] = date('Y-m-d H:i:s',$_goods['end_time']);
            $_goods['deadline_time'] = $_goods['effective_type'] == 0 ? date('Y-m-d H:i:s',$_goods['deadline_time']) : $_goods['deadline_time'];// 有效期（当effective_type=1时为天数）
            if(in_array($_goods['group_cate'],['normal','course_appoint'])){
                $_goods['detail_url'] = get_base_url('',1).'pages/group/v1/groupDetail/index?group_id='.$_goods['group_id'];
            }elseif($_goods['trade_info'] == 'hotel'){
                $_goods['detail_url'] = cfg('site_url').'/wap.php?g=Wap&c=Group&a=detail&group_id='.$_goods['group_id'];
            }else{
                $_goods['detail_url'] = get_base_url('',1).'pages/store/v1/home/index?group_id='.$_goods['group_id'];
            }
                
        }
// print_r($list);
        
        $returnArr['list'] = $list;
        $returnArr['total'] = $total;
        return $returnArr;
    }

    /**获取团购商品运费
     * @param $param
     * @return array
     */
	public function getExpressFee($groupId=0, $money=0, $address=array()){

		$nowGroup = $this->getOne(array('group_id'=>$groupId));
		$expressFee = $nowGroup['express_fee'];

		if(!empty($nowGroup['express_template_id'])){

			$expressFeeList = (new ExpressTemplateService())->getExpressList(['e.id'=>$nowGroup['express_template_id']]);

			foreach ($expressFeeList as &$v) {
				if($v['freight_type'] == 1 && $v['freight'] == 0){
					$v['freight'] = $v['first_freight'];
				}
				
                // 添加同城
                if ($v['mer_id'] && $v['area_id'] == 0) {
                    $merchant = (new MerchantService())->getMerchantByMerId($v['mer_id']);
                    $v['area_id'] = $merchant['status']==1 ? $merchant['city_id'] : 0;
                }

				if($v['area_id'] == $address['area']){

					if($money>=$v['full_money'] && $v['full_money']!=0){
						$v['freight'] = 0;
						return $v;
					}
					return $v;
				}
				if($v['area_id'] ==$address['city']){

					if($money>=$v['full_money'] && $v['full_money']!=0 ){
						$v['freight'] = 0;
						return $v;
					}
					return $v;
				}
				if($v['area_id'] == $address['province']){

					if($money>=$v['full_money'] && $v['full_money']!=0){
						$v['freight'] = 0;
						return $v;
					}
					return $v;
				}
			}
		}
		return array('freight'=>$expressFee,'full_money'=>'0');

	}

    /**
     * 根据店铺ID获取存在售卖商品的标签组
     * @author: 张涛
     * @date: 2021/05/22
     */
    public function getLabelsWithGoodsByStoreId($storeId)
    {
        $tm = time();
        $where = [
            ['s.store_id', '=', $storeId],
            ['g.status', '=', 1],
            ['g.label_group', '>', 0],
            ['g.begin_time', '<', $tm],
            ['g.end_time', '>', $tm],
        ];
        $label = $this->groupModel->alias('g')
            ->join('group_store s', 's.group_id = g.group_id')
            ->where($where)
            ->column('g.label_group');
        if (empty($label)) {
            return [];
        }

        $inLabelStr = '(' . implode(',', $label) . ')';
        $rawWhere = sprintf('is_del=0 AND (label_id IN %s OR fid IN %s)', $inLabelStr, $inLabelStr);
        return (new GroupLabelService())->getLabelTreeV2($rawWhere, true);
    }

    /**
     * 根据标签获取团购商品
     * @author: 张涛
     * @date: 2021/05/24
     */
    public function getStoreGoupGoodsByLabel($storeId, $fid = 0, $id = 0,$source='other')
    {
        $rawArr = [];
        if ($fid) {
            $rawArr[] = 'g.label_group = ' . intval($fid);
        }
        if ($id) {
            $rawArr[] = 'FIND_IN_SET(' . intval($id) . ',g.label_ids)';
        }
        $rawArr = implode(' AND ', $rawArr);

        //普通团购
        $normalGoods = $this->getGoodsByStoreId($storeId, 'normal', false, $rawArr, 0,0,$source);
        //课程团购
        $courseGoods = $this->getGoodsByStoreId($storeId, 'course_appoint', false, $rawArr, 0,0,$source);
        return ['normal_goods' => $normalGoods, 'course_goods' => $courseGoods];
    }


    public function getSceneByDate($storeId, $date)
    {
        if ($storeId < 1 || empty($date)) {
            throw new Exception(L_('店铺参数有误'), 1001);
        }

        $where = [
            ['s.store_id', '=', $storeId],
            ['g.status', '=', 1],
            ['g.group_cate', '=', 'booking_appoint'],
        ];
        $goods = $this->groupModel->alias('g')
            ->join('group_store s', 's.group_id = g.group_id')
            ->field('g.group_id,g.s_name')
            ->where($where)
            ->order('g.group_id', 'DESC')
            ->select()
            ->toArray();

        $appointService = new GroupBookingAppointService();

        //获取一天的场次预约
        $bookingAppoint = [];
        foreach ($goods as $gv) {
            $ruleInfo = $appointService->getRuleInfo($gv['group_id'], $date);
            if (empty($ruleInfo)) {
                continue;
            }
            $gv['rule_info'] = $ruleInfo;
            $bookingAppoint[] = $gv;
        }
        return $bookingAppoint;
    }

    // 添加浏览数量
    public function addHits($groupId)
    {
       
        $res = $this->groupModel->where(['group_id' => $groupId])->inc('hits',1)->update();

        return $res;
    }

    // 分销员列表
    public function getRatioList($param)
    {
        $where = [['is_del', '=', 0]];
        if($param['store_id'] > 0){
            $where[] = ['store_id','=',$param['store_id']];
        }
        $ratio_list = (new StoreMarketingPersonStore())->where($where)->select();
        $returnArr = [];
        if($ratio_list){
            foreach($ratio_list as $val){
                $perName = (new StoreMarketingPerson())->where(['id'=>$val['person_id']])->field('name')->find();
                if($perName){
                    $temp = [
                        'id' => $val['id'],
                        'name' => $perName['name'],
                    ];
                    $returnArr['ratio_list'][] =  $temp;
                }
            }
        }
        return $returnArr;
    }

    /*
    * 获取商品积分比例
    */
    public function getGoodsScore($goodsId)
    {
        $scorePercent = Group::where('group_id', $goodsId)->value('score_percent');
        if(strpos($scorePercent,'%')){
            $scorePercent = bcdiv($scorePercent,100,2);
        }
        if(empty(floatval($scorePercent))){
            return -1;
        }
        return $scorePercent;
    }
}