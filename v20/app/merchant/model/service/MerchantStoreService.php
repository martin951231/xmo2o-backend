<?php
/**
 * 店铺service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/27 11:58
 */

namespace app\merchant\model\service;

use app\common\model\db\Area;
use app\common\model\db\Merchant;
use app\common\model\db\User;
use app\common\model\db\UserLevel;
use app\common\model\service\AreaService;
use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\diypage\DiypageModelService;
use app\common\model\service\export\ExportService;
use app\common\model\service\ReplyService;
use app\common\model\service\weixin\RecognitionService;
use app\foodshop\model\service\order\DiningOrderService;
use app\foodshop\model\service\store\MerchantStoreFoodshopService;
use app\group\model\db\GroupStore;
use app\group\model\service\appoint\GroupAppointService;
use app\group\model\service\GroupService;
use app\group\model\service\StoreGroupService;
use app\merchant\model\db\AreaMarket;
use app\merchant\model\db\Keywords;
use app\merchant\model\db\MerchantCategory;
use app\merchant\model\db\MerchantStore as MerchantStoreModel;
use app\merchant\model\db\MerchantStore;
use app\merchant\model\db\MerchantStoreAuthfile;
use app\merchant\model\db\MerchantStoreCashBackLog;
use app\merchant\model\db\MerchantStoreOpenTime;
use app\merchant\model\db\MerchantStoreSlider;
use app\merchant\model\db\MerchantStoreTradeTicket;
use app\merchant\model\db\ShopDiscount;
use app\merchant\model\db\StorePay;
use app\merchant\model\service\card\CardNewService;
use app\merchant\model\service\store\MerchantCategoryService;
use app\new_marketing\model\db\NewMarketingStore;
use app\new_marketing\model\service\ClassPriceService;
use app\qa\model\service\AskService;
use app\shop\model\service\store\MerchantStoreShopService;
use dingding\Dingding;
use think\Exception;
use map\longLat;

require_once '../extend/phpqrcode/phpqrcode.php';

class MerchantStoreService
{
    public $merchantStoreModel = null;
    public $send_time_type = null;
    public $defaultSendTime = 0; // getSelectTime方法里的 默认的期望送达时间。用于传递值使用
    public $sort = array(1,2,3);//3个时间段
    public function __construct()
    {
        $this->merchantStoreModel = new MerchantStoreModel();
        $this->send_time_type = [
            L_('分钟'),
            L_('小时'),
            L_('天'),
            L_('周'),
            L_('月'),
        ];
    }

    /**
     * @return array
     * 省
     */
    public function ajax_province()
    {
        $database_area = (new Area());
        $condition_area = [['is_open', '=', 1], ['area_type', '=', 1]];
        $order = "area_sort DESC,area_id ASC";
        $data = $database_area->getSome($condition_area, true, $order)->toArray();
        $cat_id_arrs = array();
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $cat_id_arr['value'] = $value['area_id'];
                $cat_id_arr['label'] = $value['area_name'];
                $cat_id_arr['children'] = $this->ajax_city($value['area_id']);
                $cat_id_arrs[] = $cat_id_arr;
            }
        }

        return $cat_id_arrs;
    }

    public function ajax_city($area_id)
    {
        $database_area = (new Area());
        $condition_area = [['is_open', '=', 1], ['area_pid', '=', $area_id]];
        $order = "area_sort DESC,area_id ASC";
        $data = $database_area->getSome($condition_area, true, $order)->toArray();
        $city_list = array();
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $cat_id_arr['value'] = $value['area_id'];
                $cat_id_arr['label'] = $value['area_name'];
                $cat_id_arr['children'] = $this->ajax_area($value['area_id']);
                $city_list[] = $cat_id_arr;
            }
        }
        return $city_list;
    }

    public function ajax_area($area_id)
    {
        $database_area = (new Area());
        $condition_area = [['is_open', '=', 1], ['area_pid', '=', $area_id]];
        $order = "area_sort DESC,area_id ASC";
        $data = $database_area->getSome($condition_area, true, $order)->toArray();
        $city_list = array();
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $cat_id_arr['value'] = $value['area_id'];
                $cat_id_arr['label'] = $value['area_name'];
                $cat_id_arr['children'] = $this->ajax_circle($value['area_id']);
                $city_list[] = $cat_id_arr;
            }
        }
        return $city_list;
    }

    public function ajax_circle($area_id)
    {
        $database_area = (new Area());
        $condition_area = [['is_open', '=', 1], ['area_pid', '=', $area_id]];
        $order = "area_sort DESC,area_id ASC";
        $data = $database_area->getSome($condition_area, true, $order)->toArray();
        $city_list = array();
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $cat_id_arr['value'] = $value['area_id'];
                $cat_id_arr['label'] = $value['area_name'];
                $cat_id_arr['children'] = $this->ajax_area($value['area_id']);
                $city_list[] = $cat_id_arr;
            }
        }
        return $city_list;
    }

    public function ajax_market($area_id)
    {
        $database_area = (new AreaMarket());
        $condition_area = [['is_open', '=', 1], ['area_id', '=', $area_id]];
        $field = "market_id,market_name";
        $data = $database_area->getSome($condition_area, $field)->toArray();
        $city_list = array();
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $cat_id_arr['value'] = $value['market_id'];
                $cat_id_arr['label'] = $value['market_name'];
                $city_list[] = $cat_id_arr;
            }
        }
        return $city_list;
    }

    /**
     * @param $buy_id
     * @return false|int
     * 获取店铺到期时间
     */
    public function getStoreYear($buy_id){
        $year_msg=(new NewMarketingStore())->getOne(['id'=>$buy_id]);
        if(!empty($year_msg)){
            $year_msg=$year_msg->toArray();
            $end_time=strtotime("+".$year_msg['years_num']." year");
        }else{
            $end_time=time();
        }
       return $end_time;
    }

    /**
     * @param $mer_id
     * @return float
     * 剩余店铺数量
     */
    public function getStoreSum($mer_id){
        $sum1=(new NewMarketingStore())->getSum(['mer_id'=>$mer_id],'store_count');
        $sum2=(new NewMarketingStore())->getSum(['mer_id'=>$mer_id],'used_count');
        $sum=$sum1-$sum2;
        return $sum>0?$sum:0;
    }

    /**
     * @param $buy_id
     * @return array
     * 购买店铺记录表信息
     */
    public function getStoreMerMsg($buy_id){
        $msg=(new NewMarketingStore())->getOne(['id'=>$buy_id]);
        if(empty($msg)){
            $msg=[];
        }else{
            $msg=$msg->toArray();
        }
        return $msg;
    }

    /**
     * @param $where
     * @param $field
     * @return mixed
     * 增加已使用开店数量
     */
    public function setInc($where,$field){
       return (new NewMarketingStore())->setInc($where,$field);
    }
    /**
     * 获取店铺信息并处理
     * @param $storeId int
     * @return array
     */
    public function getStoreInfo($storeId)
    {
        if (!$storeId) {
            return [];
        }

        $store = $this->getStoreByStoreId($storeId);
        if (!$store) {
            return [];
        }

        // 店铺图片
        $images = (new \app\merchant\model\service\storeImageService())->getAllImageByPath($store['pic_info']);
        $store['logo'] = $store['logo'] ? thumb(replace_file_domain($store['logo']),60) : '';
        $store['pic_info_arr'] = $images;
        $store['image_old'] = $images ? $images[0] : '';
        $store['image'] = $store['image_old'] ? thumb($store['image_old'], 112) : '';
        $store['video_url'] = $store['video_url'] ? replace_file_domain($store['video_url']) : '';

        return $store;
    }

    public function getKeyWordSome($where_keywords)
    {
        return (new Keywords())->getSome($where_keywords)->toArray();
    }

    public function getStorePaySome($where_pay)
    {
        return (new StorePay())->getSome($where_pay)->toArray();
    }

    public function getStorePayUpdate($where, $data)
    {
        return (new StorePay())->updateThis($where, $data);
    }

    public function delStorePay($where)
    {
        return (new StorePay())->delData($where);
    }

    public function storePayadd($store_pay_data)
    {
        return (new StorePay())->add($store_pay_data);
    }

    public function authfileGetOne($where)
    {
        return (new MerchantStoreAuthfile())->getOne($where);
    }

    public function authfileAdd($data)
    {
        return (new MerchantStoreAuthfile())->add($data);
    }

    public function authfileUpdateThis($where, $data)
    {
        return (new MerchantStoreAuthfile())->updateThis($where, $data);
    }

    public function userLevelSome()
    {
        return (new UserLevel())->getSome([], true, 'id ASC')->toArray();
    }

    public function getMerchantInfo($mer_id)
    {
        $merchant = new Merchant();
        return $merchant->getInfo($mer_id);
    }

    public function storeDel($where)
    {
        //之前没有判单有团购关联商品不能删除
        if((new \app\merchant\model\db\GroupStore())->where($where)->find()){
            throw new \think\Exception(L_('该店铺下有'.cfg('group_alias_name').'，请先解除店铺与对应'.cfg('group_alias_name').'的关系才能删除'));
        }

        return (new MerchantStore())->where($where)->save(['status'=>4]); //之前是直接删除现在改成软删除
    }

    public function getShopDiscountCount($where)
    {
        return (new ShopDiscount())->getCount($where);
    }

    public function getShopDiscountAdd($data)
    {
        return (new ShopDiscount())->add($data);
    }

    public function getShopDiscountOne($where)
    {
        return (new ShopDiscount())->getOne($where);
    }

    public function shopDiscountUpdateThis($where, $data)
    {
        return (new ShopDiscount())->updateThis($where, $data);
    }

    public function shopDiscountDel($condition_store_discount)
    {
        return (new ShopDiscount())->getDel($condition_store_discount);
    }

    public function getStoreSliderCount($where)
    {
        return (new MerchantStoreSlider())->getCount($where);
    }

    public function getStoreSliderSome($where, $field = '*', $order, $page, $pageSize)
    {
        return (new MerchantStoreSlider())->getSome($where, $field, $order, $page, $pageSize)->toArray();
    }

    public function getStoreSliderOne($where)
    {
        return (new MerchantStoreSlider())->getOne($where);
    }

    public function getStoreSliderDel($where)
    {
        return (new MerchantStoreSlider())->del($where);
    }

    public function getStoreSliderAdd($data)
    {
        return (new MerchantStoreSlider())->add($data);
    }

    public function StoreSliderUpdateThis($where, $data)
    {
        return (new MerchantStoreSlider())->updateThis($where, $data);
    }

    //商家分类
    public function ajax_cat_fid()
    {
        $condition_area = [['cat_fid', '=', 0], ['cat_status', '=', 1]];
        $field = "cat_id,cat_fid,cat_name";
        $order = "cat_sort DESC,cat_id ASC";
        $province_list = (new MerchantCategory())->getSome($condition_area, $field, $order)->toArray();
        $cat_id_arrs = array();
        if (!empty($province_list)) {
            foreach ($province_list as $key => $val) {
                $cat_id_arr['value'] = $val['cat_id'];
                $cat_id_arr['label'] = $val['cat_name'];
                $cat_id_arr['children'] = $this->ajax_cat_id($val['cat_id']);
                $cat_id_arrs[] = $cat_id_arr;
            }
        }
        return $cat_id_arrs;
    }

    //商家子分类
    public function ajax_cat_id($cat_id)
    {
        //$cat_id = $this->request->param('cat_id', '', 'intval');
        if (empty($cat_id)) {
            return api_output(1001, [], '分类id不能为空');
        }
        $condition_area = [['cat_fid', '=', $cat_id], ['cat_status', '=', 1]];
        $field = "cat_id,cat_fid,cat_name";
        $order = "cat_sort DESC,cat_id ASC";
        $province_list = (new MerchantCategory())->getSome($condition_area, $field, $order)->toArray();
        $cat_id_arrs = array();
        if (!empty($province_list)) {
            foreach ($province_list as $key => $val) {
                $cat_id_arr['value'] = $val['cat_id'];
                $cat_id_arr['label'] = $val['cat_name'];
                $cat_id_arrs[] = $cat_id_arr;
            }
        }
        return $cat_id_arrs;
    }

    /*  public function updateThis($where_update, $updata)
      {
          return (new MerchantStore())->updateThis($where_update, $updata);
      }*/

    public function merchantStoreAdd($postData)
    {
        return (new MerchantStore())->add($postData);
    }

    public function getAreaOne($where_pro)
    {
        return (new Area())->getOne($where_pro);
    }

    public function createUpdateN($postDataNew)
    {
        return (new Dingding())->createUpdateN($postDataNew);
    }

    public function keywordsAdd($data_keywords)
    {
        return (new Keywords())->add($data_keywords);
    }

    public function keywordsDel($where)
    {
        return (new Keywords())->where($where)->delete();
    }

    public function getTradeTicketOne($condition_store_trade_ticket)
    {
        return (new MerchantStoreTradeTicket())->getOne($condition_store_trade_ticket);
    }

    public function updateTradeTicket($condition_store_trade_ticket, $store_trade_ticket)
    {
        return (new MerchantStoreTradeTicket())->updateThis($condition_store_trade_ticket, $store_trade_ticket);
    }

    public function addTradeTicket($store_trade_ticket)
    {
        return (new MerchantStoreTradeTicket())->add($store_trade_ticket);
    }

    public function merchantDiscountSome($where, $field = true, $order = true, $page, $pageSize)
    {
        return (new ShopDiscount())->getSome($where, $field, $order, $page, $pageSize)->toArray();
    }

    /**
     * 获得店铺营业时间
     * @param $storeId int
     * @return array
     */
    public function getStoreShowTime($store)
    {
        $returnArr = [];

        $store['business_time'] = '';
        if ($store['open_1'] == '00:00:00' && $store['close_1'] == '00:00:00') {
            $store['business_time'] = '24小时营业';
        } else {
            $now_time = time();
            $isClose = 1;
            $open_1 = strtotime(date('Y-m-d') . $store['open_1']);
            $close_1 = strtotime(date('Y-m-d') . $store['close_1']);
            if ($open_1 >= $close_1) {
                $close_1 += 86400;
            }
            if ($open_1 < $now_time && $now_time < $close_1) {
                $isClose = 0;
            }
            if ($store['open_2'] != '00:00:00' || $store['close_2'] != '00:00:00') {
                $open_2 = strtotime(date('Y-m-d') . $store['open_2']);
                $close_2 = strtotime(date('Y-m-d') . $store['close_2']);
                if ($open_2 >= $close_2) {
                    $close_2 += 86400;
                }
                if ($open_2 < $now_time && $now_time < $close_2) {
                    $isClose = 0;
                }
            }
            if ($store['open_3'] != '00:00:00' || $store['close_3'] != '00:00:00') {
                $store['business_time'] .= ';' . substr($store['open_3'], 0, -3) . '~' . substr($store['close_3'], 0, -3);
                $open_3 = strtotime(date('Y-m-d') . $store['open_3']);
                $close_3 = strtotime(date('Y-m-d') . $store['close_3']);
                if ($open_3 >= $close_3) {
                    $close_3 += 86400;
                }
                if ($open_3 < $now_time && $now_time < $close_3) {
                    $isClose = 0;
                }
            }
        }

        return $returnArr;
    }

    /* 获得店铺列表
     * @param $param
     * @return array
     *
     */
    public function getStoreListLimit($param = [])
    {
        $page = (isset($param['page']) && $param['page'] > 0) ? intval($param['page']) : 1;
        $pageSize = (isset($param['pageSize']) && $param['pageSize'] > 0) ? intval($param['pageSize']) : 10;
        $where[] = ['status', '=', 1];
        if (isset($param['keyword']) && $param['keyword']) {
            $where[] = ['name', 'like', '%' . $param['keyword'] . '%'];
        }

        $order['store_id'] = 'DESC';
        $returnArr = [];
        $returnArr['page_size'] = $pageSize;
        $list = $this->getSome($where, true, $order, $page, $pageSize);
        $returnArr['list'] = $list;
        return $returnArr;
    }

    /**
     * 获取店铺
     * @param $storeId int
     * @return array
     */
    public function getStoreByStoreId($storeId)
    {
        if (!$storeId) {
            return [];
        }

        $store = $this->merchantStoreModel->getStoreByStoreId($storeId);
        if (!$store) {
            return [];
        }

        return $store->toArray();
    }

    /**
     * 根据条件获取其他模块店铺列表
     * @param $tableName string 其它店铺表名
     * @param $where array 条件
     * @param $order array 排序
     * @param $field string 查询字段
     * @param $page int 当前页数
     * @param $pageSize int 每页显示数量
     * @return array
     */
    public function getStoreListByModule($tableName, $where = [], $order = [], $field = 's.*,ms.*,mm.*', $page = '1', $pageSize = '10')
    {
        if (empty($tableName)) {
            return [];
        }

        $storeList = $this->merchantStoreModel->getStoreListByModule($tableName, $where, $order, $field, $page, $pageSize);
//         var_dump($this->merchantStoreModel->getLastSql());
        if (!$storeList) {
            return [];
        }

        return $storeList->toArray();
    }

    /**
     * 根据条件获取其他模块店铺总数
     * @param $tableName string 其它店铺表名
     * @param $where array 条件
     * @return array
     */
    public function getStoreCountByModule($tableName, $where = [])
    {
        if (empty($tableName)) {
            return '0';
        }

        $storeCount = $this->merchantStoreModel->getStoreCountByModule($tableName, $where);
        // var_dump($this->merchantStoreModel->getLastSql());
        if (!$storeCount) {
            return '0';
        }

        return $storeCount;
    }

    /**
     * 获得配送和自提时间
     * @param $return array
     * @param $where array 条件
     * @return array
     */
    public function getSelectTime($return, $paramTime = null, $n = 0, $second = 900, $return_s = 0, $reduce = 0)
    {
        //DUMP($return);EXIT;
        if ($paramTime === null) {
            $paramTime = time();
        }

        //预订的天数
        $advance_day = isset($return['store']['advance_day']) ? $return['store']['advance_day'] : 0;

        //配送时间段
        $start_time = isset($return['store']['delivertime_start']) ? $return['store']['delivertime_start'] : 0;
        $stop_time = isset($return['store']['delivertime_stop']) ? $return['store']['delivertime_stop'] : 0;
        $start_time2 = isset($return['store']['delivertime_start2']) ? $return['store']['delivertime_start2'] : 0;
        $stop_time2 = isset($return['store']['delivertime_stop2']) ? $return['store']['delivertime_stop2'] : 0;
        $start_time3 = isset($return['store']['delivertime_start3']) ? $return['store']['delivertime_start3'] : 0;
        $stop_time3 = isset($return['store']['delivertime_stop3']) ? $return['store']['delivertime_stop3'] : 0;

        if ($start_time == $stop_time && $start_time == '00:00:00') {//时间段一，24小时
            $start_time = '00:00:00';
            $stop_time = '23:59:59';
        } elseif ($start_time2 == $stop_time2 && $start_time2 == '00:00:00') {//没有时间段二
            $stop_time2 = $start_time2 = 0;
        }
        if ($start_time3 == $stop_time3 && $start_time3 == '00:00:00') {//没有时间段三
            $stop_time3 = $start_time3 = 0;
        }

        //不受配送时间限制
        if ($n) {
            $start_time = '00:00:00';
            $stop_time = '23:59:59';
            $stop_time2 = $start_time2 = 0;
            $stop_time3 = $start_time3 = 0;
        }

        $return['store']['work_time'] = 0;
        //出单时长的单位
        $diffTime = 60;
        $order_type = isset($return['order_type']) ? $return['order_type'] : 0;
        if ($order_type == 2) {
            $type_key = 'mall_send_time_type';
            $return['store']['work_time'] = $return['store']['mall_work_time'];
        } else {
            $type_key = 'send_time_type';
        }
        if (isset($return['store'][$type_key]) && $return['store'][$type_key] == 1) {
            $diffTime = 3600;
        } elseif (isset($return['store'][$type_key]) && $return['store'][$type_key] == 2) {
            $diffTime = 86400;
        } elseif (isset($return['store'][$type_key]) && $return['store'][$type_key] == 3) {
            $diffTime = 86400 * 7;
        } elseif (isset($return['store'][$type_key]) && $return['store'][$type_key] == 4) {
            $diffTime = 86400 * 30;
        }
        //dump($return['store']['send_time_type']);exit;
        //营业时间
        $open_1 = $return['store']['open_1'];
        $close_1 = $return['store']['close_1'];
        $open_2 = $return['store']['open_2'];
        $close_2 = $return['store']['close_2'];
        $open_3 = $return['store']['open_3'];
        $close_3 = $return['store']['close_3'];

        $send_time = 0;
        if (isset($return['deliver_speed_hour']) && get_format_number($return['deliver_speed_hour']) && $return['store']['send_time_type'] == 0 && isset($return['distance'])) {    //按时速计算
            $send_time = $return['distance'] / $return['deliver_speed_hour'] * 3600 + ($return['deliver_delay_time'] * 60);
        } else {
            isset($return['store']['send_time']) && $send_time = $return['store']['send_time'] * 60;
        }

        $nowTime = 0;
        if (isset($return['store']['send_time'])) {
            if ($reduce > $return['store']['work_time'] * $diffTime + $send_time || $reduce == '-1') {
                $nowTime = $paramTime;//默认的期望送达时间
            } else {
                $nowTime = $paramTime + $return['store']['work_time'] * $diffTime + $send_time;//默认的期望送达时间
            }

            if ($second == 1200) {
                if ($reduce > $return['store']['work_time'] * $diffTime || $reduce == '-1') {
                    $nowTime = $paramTime;
                } else {
                    $nowTime = $paramTime + $return['store']['work_time'] * $diffTime;
                }
            }
        }

        $nowTime = strtotime(date('Y-m-d H:i', $nowTime));
        if ($second == 900 && !$this->defaultSendTime) {
            $this->defaultSendTime = $nowTime;
        }

        $addTime = $return['store']['work_time'] * $diffTime + $send_time;
        if ($second == 1200) {
            $addTime = $return['store']['work_time'] * $diffTime;
        }

        // 立即送达时间
        $right_row_time = time() + $return['store']['work_time'] * $diffTime + $send_time;

        //直接返回增加的秒数
        if ($return_s == 1) {
            return $addTime;
        }

        //营业时间第一时间段的  开始时间等于结束时间 证明就是全天营业
        if ($open_1 == $close_1) {
            $open_1 = '00:00:00';
            $close_1 = '23:59:59';
            $open_2 = $close_2 = $open_3 = $close_3 = '00:00:00';
        }

        if ($nowTime >= strtotime(date('Y-m-d 23:59:59'))) {
            $open_2 = '00:00:00';
            $close_2 = '23:59:59';
        }

        $ot1 = strtotime(date('Y-m-d ' . $open_1));
        $ct1 = strtotime(date('Y-m-d ' . $close_1));
        $ot2 = strtotime(date('Y-m-d ' . $open_2));
        $ct2 = strtotime(date('Y-m-d ' . $close_2));
        $ot3 = strtotime(date('Y-m-d ' . $open_3));
        $ct3 = strtotime(date('Y-m-d ' . $close_3));

        $s1 = $s2 = $s3 = $e1 = $e2 = $e3 = 0;

        $businessTime = array();
        if ($ot1 != $ct1) {
            $s1 = $ot1;
            $e1 = $ct1;
            if ($ot1 > $ct1) {
                $ct1 += 86400;
            }
            $businessTime[] = array(
                's' => $ot1 + $addTime,
                'e' => $ct1 + $addTime
            );
        }
        if ($ot2 != $ct2) {
            $s2 = $ot2;
            $e2 = $ct2;
            if ($ot2 > $ct2) {
                $ct2 += 86400;
            }
            $businessTime[] = array(
                's' => $ot2 + $addTime,
                'e' => $ct2 + $addTime
            );
        }
        if ($ot3 != $ct3) {
            $s3 = $ot3;
            $e3 = $ct3;
            if ($ot3 > $ct3) {
                $ct3 += 86400;
            }
            $businessTime[] = array(
                's' => $ot3 + $addTime,
                'e' => $ct3 + $addTime
            );
        }

        if ($e1 - $s2 > 86280 && $e1 - $s2 < 86400) {//时间段1和时间段2隔天相连
            $businessTime[] = array(
                's' => strtotime(date('Y-m-d ' . $open_2)) + 86400,
                'e' => strtotime(date('Y-m-d ' . $close_2)) + 86400 + $addTime
            );
        }
        if ($e2 - $s1 > 86280 && $e2 - $s1 < 86400) {//时间段2和时间段1隔天相连
            $businessTime[] = array(
                's' => strtotime(date('Y-m-d ' . $open_1)) + 86400,
                'e' => strtotime(date('Y-m-d ' . $close_1)) + 86400 + $addTime
            );
        }
        if ($e1 - $s3 > 86280 && $e1 - $s3 < 86400) {//时间段1和时间段3隔天相连
            $businessTime[] = array(
                's' => strtotime(date('Y-m-d ' . $open_3)) + 86400,
                'e' => strtotime(date('Y-m-d ' . $close_3)) + 86400 + $addTime
            );
        }
        if ($e3 - $s1 > 86280 && $e3 - $s1 < 86400) {//时间段3和时间段1隔天相连
            $businessTime[] = array(
                's' => strtotime(date('Y-m-d ' . $open_1)) + 86400,
                'e' => strtotime(date('Y-m-d ' . $close_1)) + 86400 + $addTime
            );
        }
        if ($e2 - $s3 > 86280 && $e2 - $s3 < 86400) {//时间段2和时间段3隔天相连
            $businessTime[] = array(
                's' => strtotime(date('Y-m-d ' . $open_3)) + 86400,
                'e' => strtotime(date('Y-m-d ' . $close_3)) + 86400 + $addTime
            );
        }
        if ($e3 - $s2 > 86280 && $e3 - $s2 < 86400) {//时间段3和时间段2隔天相连
            $businessTime[] = array(
                's' => strtotime(date('Y-m-d ' . $open_2)) + 86400,
                'e' => strtotime(date('Y-m-d ' . $close_2)) + 86400 + $addTime
            );
        }

        $startTime1 = strtotime(date('Y-m-d ' . $start_time));
        $stopTime1 = strtotime(date('Y-m-d ' . $stop_time));

        $deliverTimeList = array();
        $delivery_fee_old = isset($return['delivery_fee_old']) ? $return['delivery_fee_old'] : 0;
        $delivery_fee = isset($return['delivery_fee']) ? $return['delivery_fee'] : 0;
        $s_basic_price1 = isset($return['store']['s_basic_price1']) ? $return['store']['s_basic_price1'] : 0;
        if ($startTime1 > $stopTime1) {
            $deliverTimeList[] = array(
                's' => strtotime(date('Y-m-d 00:00:00')),
                'e' => $stopTime1,
                'select' => 1,
                'delivery_fee_old' => floatval(round($delivery_fee_old, 2)),
                'delivery_fee' => floatval(round($delivery_fee, 2)),
                's_basic_price' => floatval(round($s_basic_price1, 2))
            );
            $deliverTimeList[] = array(
                's' => $startTime1,
                'e' => $stopTime1 + 86400,
                'select' => 1,
                'delivery_fee_old' => floatval(round($delivery_fee_old, 2)),
                'delivery_fee' => floatval(round($delivery_fee, 2)),
                's_basic_price' => floatval(round($s_basic_price1, 2))
            );
        } else {
            $deliverTimeList[] = array(
                's' => $startTime1,
                'e' => $stopTime1,
                'select' => 1,
                'delivery_fee_old' => floatval(round($delivery_fee_old, 2)),
                'delivery_fee' => floatval(round($delivery_fee, 2)),
                's_basic_price' => floatval(round($s_basic_price1, 2)),
            );
        }
        $startTime2 = 0;
        $stopTime2 = 0;
        if ($start_time2 != '0' && $stop_time2 != '0') {
            $startTime2 = strtotime(date('Y-m-d ' . $start_time2));
            $stopTime2 = strtotime(date('Y-m-d ' . $stop_time2));
            if ($startTime2 > $stopTime2) {
                $deliverTimeList[] = array(
                    's' => strtotime(date('Y-m-d 00:00:00')),
                    'e' => $stopTime2,
                    'select' => 2,
                    'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee2'], 2)),
                    's_basic_price' => floatval(round($return['store']['s_basic_price2'], 2))
                );
                $deliverTimeList[] = array(
                    's' => $startTime2,
                    'e' => $stopTime2 + 86400,
                    'select' => 2,
                    'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee2'], 2)),
                    's_basic_price' => floatval(round($return['store']['s_basic_price2'], 2))
                );
            } else {
                $deliverTimeList[] = array(
                    's' => $startTime2,
                    'e' => $stopTime2,
                    'select' => 2,
                    'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee2'], 2)),
                    's_basic_price' => floatval(round($return['store']['s_basic_price2'], 2)),
                );
            }
        }
        $startTime3 = 0;
        $stopTime3 = 0;
        if ($start_time3 != '0' && $stop_time3 != '0') {
            $startTime3 = strtotime(date('Y-m-d ' . $start_time3));
            $stopTime3 = strtotime(date('Y-m-d ' . $stop_time3));
            if ($startTime3 > $stopTime3) {
                $deliverTimeList[] = array(
                    's' => strtotime(date('Y-m-d 00:00:00')),
                    'e' => $stopTime3,
                    'select' => 3,
                    'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee3'], 2)),
                    's_basic_price' => floatval(round($return['store']['s_basic_price3'], 2))
                );
                $deliverTimeList[] = array(
                    's' => $startTime3,
                    'e' => $stopTime3 + 86400,
                    'select' => 3,
                    'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee3'], 2)),
                    's_basic_price' => floatval(round($return['store']['s_basic_price3'], 2))
                );
            } else {
                $deliverTimeList[] = array(
                    's' => $startTime3,
                    'e' => $stopTime3,
                    'select' => 3,
                    'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                    'delivery_fee' => floatval(round($return['delivery_fee3'], 2)),
                    's_basic_price' => floatval(round($return['store']['s_basic_price3'], 2))
                );
            }
        }

        if ($stopTime1 - $startTime2 > 86330 && $stopTime1 - $startTime2 < 86400) {//时间段1和时间段2隔天相连
            $startTime2 += 86400;
            $stopTime2 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime2,
                'e' => $stopTime2,
                'select' => 2,
                'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee2'], 2)),
                's_basic_price' => floatval(round($return['store']['s_basic_price2'], 2))
            );
        }
        if ($stopTime2 - $startTime1 > 86330 && $stopTime2 - $startTime1 < 86400) {//时间段2和时间段1隔天相连
            $startTime1 += 86400;
            $stopTime1 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime1,
                'e' => $stopTime1,
                'select' => 1,
                'delivery_fee_old' => floatval(round($delivery_fee_old, 2)),
                'delivery_fee' => floatval(round($delivery_fee, 2)),
                's_basic_price' => floatval(round($s_basic_price1, 2))
            );
        }

        if ($stopTime1 - $startTime3 > 86330 && $stopTime1 - $startTime3 < 86400) {//时间段1和时间段3隔天相连
            $startTime3 += 86400;
            $stopTime3 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime3,
                'e' => $stopTime3,
                'select' => 3,
                'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee3'], 2)),
                's_basic_price' => floatval(round($return['store']['s_basic_price3'], 2))
            );
        }
        if ($stopTime3 - $startTime1 > 86330 && $stopTime3 - $startTime1 < 86400) {//时间段3和时间段1隔天相连
            $startTime1 += 86400;
            $stopTime1 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime1,
                'e' => $stopTime1,
                'select' => 1,
                'delivery_fee_old' => floatval(round($delivery_fee_old, 2)),
                'delivery_fee' => floatval(round($delivery_fee, 2)),
                's_basic_price' => floatval(round($s_basic_price1, 2))
            );
        }

        if ($stopTime2 - $startTime3 > 86330 && $stopTime2 - $startTime3 < 86400) {//时间段2和时间段3隔天相连
            $startTime3 += 86400;
            $stopTime3 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime3,
                'e' => $stopTime3,
                'select' => 3,
                'delivery_fee_old' => floatval(round($return['delivery_fee3_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee3'], 2)),
                's_basic_price' => floatval(round($return['store']['s_basic_price3'], 2))
            );
        }

        if ($stopTime3 - $startTime2 > 86330 && $stopTime3 - $startTime2 < 86400) {//时间段3和时间段2隔天相连
            $startTime2 += 86400;
            $stopTime2 += 86400;
            $deliverTimeList[] = array(
                's' => $startTime2,
                'e' => $stopTime2,
                'select' => 2,
                'delivery_fee_old' => floatval(round($return['delivery_fee2_old'], 2)),
                'delivery_fee' => floatval(round($return['delivery_fee2'], 2)),
                's_basic_price' => floatval(round($return['store']['s_basic_price2'], 2))
            );
        }

        $newB = array(); // 营业时间
        $newD = array(); // 配送时间
        for ($day = 0; $day <= $advance_day; $day++) {
            foreach ($businessTime as $btime) {
                $btime['s'] += $day * 86400;
                $btime['e'] += $day * 86400;
                $newB[] = $btime;
            }
            foreach ($deliverTimeList as $dtime) {
                $dtime['s'] += $day * 86400 + ($second == 900 ? $addTime : 0);//这里的addTime以前是sendtime,注意下逻辑
                $dtime['e'] += $day * 86400 + $addTime;
                $newD[] = $dtime;
            }
        }

        foreach ($deliverTimeList as $dtime) {
            $dtime['s'] += ($advance_day + 1) * 86400 + ($second == 900 ? $addTime : 0);//这里的addTime以前是sendtime,注意下逻辑
            $dtime['e'] += ($advance_day + 1) * 86400 + $addTime;
            $newD[] = $dtime;
        }
        $newB_new = (new MerchantStoreOpenTimeService())->getTimeByWeek($return['store']['store_id']);

        $last_time_max = time() + ($advance_day + 1) * 60 * 60 * 24;
        //dump($newD);exit;
        $dateList = array();
        foreach ($newD as $row_key => $row) {
            $stime = $row['s'];
            $etime = $row['e'];
            $_m_00 = strtotime(date('Y-m-d H:00', $stime));
            $_m_15 = strtotime(date('Y-m-d H:15', $stime));
            $_m_30 = strtotime(date('Y-m-d H:30', $stime));
            $_m_45 = strtotime(date('Y-m-d H:45', $stime));

            if ($_m_00 >= $stime) {
                $stime = $_m_00;
            } elseif ($_m_15 >= $stime) {
                $stime = $_m_15;
            } elseif ($_m_30 >= $stime) {
                $stime = $_m_30;
            } elseif ($_m_45 >= $stime) {
                $stime = $_m_45;
            } else {
                $stime = strtotime(date('Y-m-d H:00', $stime + 3600));
            }

            if ($row['s'] <= $nowTime && $nowTime < $row['e']) {
                if ($this->checkBusinessTime($newB_new, $nowTime, $nowTime, $addTime, $last_time_max)) {
                    $dateList[date('Y-m-d', $nowTime)][date('H:i', $nowTime)] = array(
                        'hour_minute' => date('H:i', $nowTime),
                        'time_select' => $row['select'],
                        'delivery_fee_old' => $row['delivery_fee_old'],
                        'delivery_fee' => $row['delivery_fee'],
                        'first_asap' => $row_key == 0 ? true : false,
                        'unix_time' => $nowTime,
                        's_basic_price' => $row['s_basic_price'],
                    );

                    // 判断是否是立即送达时间
                    if (strtotime(date('H:i', $right_row_time)) == strtotime(date('H:i', $nowTime))) {
                        $dateList[date('Y-m-d', $nowTime)][date('H:i', $nowTime)]['is_right_now'] = 1;
                    }
                }
            }
            for ($nowDate = $stime; $nowDate <= $etime;) {
                if ($this->checkBusinessTime($newB_new, $nowDate, $nowTime, $addTime, $last_time_max)) {
                    $dateList[date('Y-m-d', $nowDate)][date('H:i', $nowDate)] = array(
                        'hour_minute' => date('H:i', $nowDate),
                        'time_select' => $row['select'],
                        'delivery_fee_old' => $row['delivery_fee_old'],
                        'delivery_fee' => $row['delivery_fee'],
                        'unix_time' => $nowDate,
                        's_basic_price' => $row['s_basic_price'],
                    );
                }
                $nowDate += $second;
            }
        }

        //组合时间段
        $dateList_new = array();
        foreach ($dateList as $key => $value) {
            foreach ($value as $k => $v) {
                $btime = $key . ' ' . $dateList[$key][$k]['hour_minute'] . ':00';
                $etime = date('H:i', strtotime($btime) + $second);

                //if (isset($dateList[$key][$etime])) {
                $dateList_new[$key][$k]['unix_time'] = $dateList[$key][$k]['unix_time'];
                $dateList_new[$key][$k]['first_asap'] = isset($dateList[$key][$k]['first_asap']) && $dateList[$key][$k]['first_asap'] ? true : false;
                $dateList_new[$key][$k]['hour_minute'] = $dateList[$key][$k]['hour_minute'];
                $dateList_new[$key][$k]['show_hour_minute'] = $dateList[$key][$k]['hour_minute'] . '-' . $etime;
                $dateList_new[$key][$k]['time_select'] = $dateList[$key][$k]['time_select'];

                $dateList_new[$key][$k]['s_basic_price'] = $dateList[$key][$k]['s_basic_price'];
                $dateList_new[$key][$k]['is_right_now'] = isset($dateList[$key][$k]['is_right_now']) && intval($dateList[$key][$k]['is_right_now']) ? intval($dateList[$key][$k]['is_right_now']) : 0;

                if (!$n) {
                    $dateList_new[$key][$k]['delivery_fee'] = (empty($_POST['Device-Id']) || $_POST['Device-Id'] == 'wxapp') ? $dateList[$key][$k]['delivery_fee'] : get_format_number($dateList[$key][$k]['delivery_fee']);
                    $dateList_new[$key][$k]['delivery_fee_old'] = (empty($_POST['Device-Id']) || $_POST['Device-Id'] == 'wxapp') ? $dateList[$key][$k]['delivery_fee_old'] : get_format_number($dateList[$key][$k]['delivery_fee_old']);
                }
                //}
            }
        }
        return $dateList_new;
    }

    public function checkBusinessTime($weekTime, $time, $startTime, $addTime, $last_time_max = 0)
    {

        if (empty($weekTime)) return false;
        if ($time < $startTime) return false;

        $last_end = strtotime(date("Y-m-d 23:59:59", $last_time_max));
        if ($time > $last_end) return false;

        $w = date("w", $time);
        $last_w = date("w", $last_time_max);
        $checkTime = $weekTime[$w] ?? [];
        //如果是最后一天只能取00点开始的时间段(前面的逻辑已经在预约时间的基础上多加了1天，这里是补全跨天的营业时间)
        if ($w == $last_w) {
            foreach ($checkTime as $k => $v) {
                if ($v['open_time'] <> '00:00:00' || $v['close_time'] == '00:00:00') {
                    unset($checkTime[$k]);
                }
            }
        }
        $last_end = strtotime(date('Y-m-d', $last_time_max) . ' 23:59:59');

        //这里需要判断一下昨天晚上的营业时间（包括配送时间和出单时间）是否跨越了零点的情况【营业时间设置本身没有跨越0点的情况下】
        $zero = strtotime(date('Y-m-d', $time) . ' ' . '00:00:00');
        $today_zero_add = $this->getYerterdayNight($weekTime, $time, $addTime);

        if ($time < $zero + $today_zero_add && $today_zero_add) {
            return true;
        }

        if (empty($checkTime)) return false;
        foreach ($checkTime as $key => $val) {
            if ($val['open_time'] == $val['close_time'] && $val['time_sort'] == 1) {//24小时营业
                return true;
            } elseif ($val['open_time'] == '00:00:00' && ($val['close_time'] == '23:59:00' || $val['close_time'] == '23:59:59')) {//24小时营业
                return true;
            } else {

                $start_time = strtotime(date('Y-m-d', $time) . ' ' . $val['open_time']);
                $end_time = strtotime(date('Y-m-d', $time) . ' ' . $val['close_time']);

                if ($start_time + $addTime <= $time && $end_time + $addTime >= $time) {
                    return true;
                } elseif ($zero > time() && $time < $last_end) {
                    $today_zero_add = $this->getYerterdayNight($weekTime, $time, $addTime);
                    if ($time < $zero + $today_zero_add) {
                        return true;
                    }
                }
            }
        }
        return false;
    }


    /**
     * 获取指定time前一天的的最晚关店时间差
     * @param $weekTime string
     * @param $time string
     * @param $addTime string
     * @return array
     */
    public function getYerterdayNight($weekTime, $time, $addTime)
    {
        $w = date("w", $time);
        if ($w == 0) {
            $last = 6;
        } else {
            $last = $w - 1;
        }
        $select_time = $weekTime[$last] ?? [];
        $time_arr = array();
        if (empty($select_time)) return 0;//前一天不营业
        foreach ($select_time as $key => $val) {
            if ($val['close_time'] <> '00:00:00' && $val['close_time'] <> '23:59:59' && $val['close_time'] <> '23:59:00') {//不是营业到24点
                $today_time = strtotime(date('Y-m-d', $time) . ' ' . $val['close_time']);
                $yesterday_time = $today_time - 60 * 60 * 24;
                $time_arr[] = $yesterday_time;
            } else {//营业到24点
                return $addTime;
            }
        }
        if (!empty($time_arr)) {
            $yesterda_night = max($time_arr);
        } else {//理论上不存在这种情况，加个打印，方便出现bug调试
            fdump($select_time, 'select_time_error');
        }

        if ($yesterda_night) {
            $today_zero = strtotime(date('Y-m-d', $time) . ' ' . '00:00:00');
            $ext = $today_zero - $yesterda_night;
            if ($ext > $addTime) {
                return 0;
            } else {
                return $addTime - $ext;
            }
        } else {
            return 0;
        }
    }

    /**
     * 获取优惠买单优惠信息
     * @param $discount array 其它店铺表名
     * @return array
     */
    public function getStoreQuickDiscount($discount)
    {
        if (empty($discount)) {
            return [];
        }
        // discount_type 1折扣2满减
        // discount_percent 折扣率
        // condition_price 满减条件
        // minus_price 满减金额
        $returnArr = [];
        $currencySymbol = cfg('Currency_symbol');
        if ($discount['discount_type'] == 1) {
            if ($discount['discount_percent'] == 0 || $discount['discount_percent'] == 10) {
                return [];
            }
            $returnArr['type'] = $discount['discount_type'];
            $returnArr['name'] = L_('买单立享X1折', ['X1' => $discount['discount_percent']]);
        } elseif ($discount['discount_type'] == 2) {
            $returnArr['type'] = $discount['discount_type'];
            if ($discount['minus_price'] <= 0) {
                return [];
            }
            if ($discount['condition_price'] > 0) {
                $returnArr['name'] = L_('买单每满' . $currencySymbol . 'X1减' . $currencySymbol . 'X2', ['X1' => $discount['condition_price'], 'X2' => $discount['minus_price']]);
            } else {
                $returnArr['name'] = L_('买单立减' . $currencySymbol . 'X1', ['X1' => $discount['minus_price']]);
            }

        }
        return $returnArr;
    }

    /**
     * 获得综合店铺微信二维码
     * @param $storeId integer 店铺id
     * @return array
     */
    public function seeQrcode($storeId, $type = 'merchantstore')
    {
        $qrcodeId = (new RecognitionService())->getQrcodeByThirdId($type, $storeId);
        if (!isset($qrcodeId['qrcode_id']) || !$qrcodeId['qrcode_id']) {
            $result = (new RecognitionService())->getNewQrcode($type, $storeId);
        } else {
            $result = (new RecognitionService())->getQrcode($qrcodeId['qrcode_id']);
        }

        if (isset($result['qrcode']) && $result['qrcode'] == 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=') {
            $result = (new RecognitionService())->getNewQrcode($type, $storeId);
        }
        return $result;
    }

    /**
     * 获得商家二维码id
     * @param $param array 登录信息
     * @return array
     */
    public function getQrcode($storeId)
    {
        $where = [
            'store_id' => $storeId
        ];
        $nowStore = $this->getOne($where);
        if (empty($nowStore)) {
            return false;
        }
        return $nowStore;
    }

    public function saveQrcode($storeId, $qrcodeId)
    {
        $where = [
            'store_id' => $storeId
        ];
        $data = [
            'qrcode_id' => $qrcodeId
        ];
        $result = $this->updateThis($where, $data);

        if (!$result) {
            throw new \think\Exception(L_("保存二维码至商家信息失败！请重试。"), 1003);
        }
        return true;
    }

    public function delQrcode($storeId)
    {
        $where = [
            'store_id' => $storeId
        ];
        $data = [
            'qrcode_id' => ''
        ];
        $result = $this->updateThis($where, $data);

        if (!$result) {
            throw new \think\Exception(L_("保存二维码至商家信息失败！请重试。"), 1003);
        }
        return true;
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where = [], $field = true, $order = true, $page = 0, $limit = 0)
    {
        try {
            $result = $this->merchantStoreModel->getSome($where, $field, $order, $page, $limit);
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
    public function getOne($where)
    {
        $detail = $this->merchantStoreModel->getOne($where);
        if (!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getCount($where)
    {
        $count = $this->merchantStoreModel->getCount($where);
        if (!$count) {
            return 0;
        }
        return $count;
    }

    /**
     * 根据条件返回分组总数
     * @param $where array 条件
     * @param $groupField string 分组字段
     * @return array
     */
    public function getCountByGroup($where, $groupField = '')
    {
        $count = $this->merchantStoreModel->getCountByGroup($where, $groupField);
        if (!$count) {
            return [];
        }
        return $count->toArray();
    }

    /**
     * 添加数据
     * @param $where array 条件
     * @return array
     */
    public function add($data)
    {
        if (empty($data)) {
            return false;
        }

        $result = $this->merchantStoreModel->save($data);
        if (!$result) {
            return false;
        }

        return $this->merchantStoreModel->id;
    }

    /**
     * 更新店铺数据
     * @param $where array 条件
     * @param $data array 数据
     * @return array
     */
    public function updateThis($where, $data)
    {
        if (empty($where) || !$data) {
            return false;
        }

        $result = $this->merchantStoreModel->updateThis($where, $data);
        if ($result === false) {
            return false;
        }

        return $result;
    }

    public function getStoreList($where, $page, $pageSize)
    {
        return $this->merchantStoreModel->getStoreList($where, $page, $pageSize);
    }

    public function getStoreListCount($where)
    {
        return $this->merchantStoreModel->getStoreListCount($where);
    }

    /**
     * @param $goods_id
     * @return string
     * 获取二维码
     */
    public function createQrcode($url, $store_id)
    {
        $dir = '/runtime/qrcode/mall/' . $store_id;
        $path = '../..' . $dir;
        $filename = md5($store_id.$url) . '.png';
        if (file_exists($path . '/' . $filename)) {
            return cfg('site_url') . $dir . '/' . $filename;
        }
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $qrCon = $url;
        $qrcode = new \QRcode();
        $qrcode->png($qrCon, $path . '/' . $filename, 'L', '9');
        return cfg('site_url') . $dir . '/' . $filename;
    }

    /**
     * 根据商家ID获取店铺列表
     */
    public function getStoreListByMerId($merId, $fields = '*', $where = [['status', '<>', 4]])
    {
        $where[] = ['mer_id', '=', $merId];
        return $this->merchantStoreModel->where($where)->field($fields)->select()->toArray();
    }

    /* 种草文章绑定的店铺列表
     * @param $where
     * @param $lng
     * @param $lat
     * @param $limit
     * @return array
     *
     */
    public function getGrowGrassStoreList($where, $lng, $lat, $page, $limit = 10)
    {
        $list = $this->merchantStoreModel->getGrowGrassStoreList($where, $lng, $lat, $page, $limit);
        $list = $this->formatData($list, $lng, $lat, ['show_group' => 1]);
        return $list;
    }

    /**团购商品详情 更多商家(显示距该用户直线距离最近的10家店铺)
     * @param array $where 查询条件
     * @param float $lng 用户经度
     * @param float $lat 用户维度
     * @param int $limit 返回的列表数量
     * @return array
     *
     */
    public function getGroupRecommendStoreList($where = [], $lng, $lat, $limit = 10)
    {
        $list = $this->merchantStoreModel->getGroupRecommendStoreList($where, $lng, $lat, $limit);
        $list = $this->formatData($list, $lng, $lat, ['show_group' => 1]);
        return $list;
    }

    /** 处理店铺显示字段
     * @param array $list 店铺列表
     * @param string $long
     * @param string $lat
     * @param array $extro // 其他参数
     * @return array
     *
     */
    public function formatData($list = [], $long = '', $lat = '', $extro = [])
    {
        $returnArr = [];
        if (!empty($list)) {
            foreach ($list as $store) {
                $tempStore = [];
                $tempStore['mer_id'] = $store['mer_id'];
                $tempStore['store_id'] = $store['store_id'];
                //店铺名称
                $tempStore['name'] = $store['name'];
                //评分
                $tempStore['score'] = $store['score'] == 0 ? '5.0' : $store['score'];

                $tempStore['long'] = $store['long'];
                $tempStore['lat'] = $store['lat'];
                //惠(团购)
                $tempStore['group_goods_str'] = '';
                $tempStore['group_count'] = 0;

                // 店铺图片
                $images = (new storeImageService())->getAllImageByPath($store['pic_info']);
                $tempStore['image'] = $images ? thumb(array_shift($images), 180) : '';

                //距离
                if (isset($store['juli']) && $store['juli']) {
                    $tempStore['range'] = get_range($store['juli'], false);
                } else if ($long && $lat) {
                    $location2 = (new longLat())->gpsToBaidu($store['lat'], $store['long']);//转换腾讯坐标到百度坐标
                    $jl = get_distance($location2['lat'], $location2['lng'], $lat, $long);
                    $tempStore['range'] = get_range($jl, false);
                } else {
                    $tempStore['range'] = '';
                }

                // 店铺地址
                $tempStore['address'] = isset($store['address']) ? $store['address'] : $store['adress'];

                $tempStore['url'] = get_base_url('pages/store/v1/home/index?store_id=' . $store['store_id'], true);

                // 店铺分类
                $tempStore['cate_name'] = '';
                $cateNames = [];
                if (isset($store['cat_fid']) && $store['cat_fid']) {
                    $cate = (new MerchantCategoryService())->getOne(['cat_id' => $store['cat_fid']]);
                    $cateNames[] = $cate['cat_name'] ?? '';
                }
                if (isset($store['cat_id']) && $store['cat_id']) {
                    $cate = (new MerchantCategoryService())->getOne(['cat_id' => $store['cat_id']]);
                    $cateNames[] = $cate['cat_name'] ?? '';
                    //$tempStore['cate_name'] = $tempStore['cate_name'] ? $tempStore['cate_name'].'/'.$cate['cat_name'] : $cate['cat_name'];
                }
                $tempStore['cate_name'] = implode('/', $cateNames);


                // 商圈
                $tempStore['area_name'] = "";
                if (isset($store['cat_id']) && $store['circle_id']) {
                    $area = (new AreaService())->getAreaByAreaId($store['circle_id']);
                    $tempStore['area_name'] = $area['area_name'];
                }

                // 店铺列表右侧标识
                $tempStore['bus_label'] = $this->getBusLabel($store);

                // 惠 外 买 优惠
                $tempStore['discount_list'] = (new DiypageModelService())->discountList($store);

                $tempStore['group_goods'] = ['count' => 0, 'list' => []];//样式1使用
                $group_list = (new DiypageModelService())->groupGoods($store['store_id']);
                if (!empty($group_list['list'])) {
                    $tempStore['group_goods']['count'] = $group_list['count'];
                    $items = [];
                    foreach ($group_list['list'] as $group) {
                        $item['group_id'] = $group['group_id'];
                        $item['name'] = $group['name'];
                        $item['price'] = $group['price'];
                        $item['sale_count'] = $group['sale_count'];
                        $item['group_cate'] = $group['group_cate'];
                        if ($group['group_cate'] == "normal") {
                            $item['tag'] = "团";
                        } elseif ($group['group_cate'] == "booking_appoint") {
                            $item['tag'] = "订";
                        } else {
                            $item['tag'] = "券";
                        }
                        $items[] = $item;
                    }
                    $tempStore['group_goods']['list'] = $items;
                }

                $returnArr[$store['store_id']] = $tempStore;
            }

            //惠(团购)
            $storeGroupService = new StoreGroupService();
            $storeIdArr = array_column($list, 'store_id');
            // 店铺ID
            $storeIdStr = implode(',', $storeIdArr);

            if ($storeIdArr) {
                $now_time = time();
                // 店铺ID
                $storeIdStr = implode(',', $storeIdArr);
                if (isset($extro['show_group']) && $extro['show_group']) {
                    // 团购列表
                    $storeGroupService = new StoreGroupService();
                    $field = 'distinct a.store_id, g.group_id, g.name,g.price';
                    $groupList = $storeGroupService->getNormalStoreGroup($storeIdStr, $field, ['g.price' => 'ASC']);
                    foreach ($groupList as $row) {
                        if (isset($returnArr[$row['store_id']])) {
                            $returnArr[$row['store_id']]['group_goods_str'] = $row['name'];
                        }
                    }
                }

            }
        }

        return array_values($returnArr);

    }


    /**首页附近好店 按当前位置距离、评分展示9家店铺
     * @param array $where 查询条件
     * @param float $lng 用户经度
     * @param float $lat 用户维度
     * @param int $limit 返回的列表数量
     * @return mixed
     *
     */
    public function getStoresDistance($where = [], $lng, $lat, $limit = 9)
    {
        return $this->merchantStoreModel->getStoresDistance($where, $lng, $lat, $limit);
    }

    /**频道页优选好店 按照分类下店铺评分以及销量展示店铺
     * @param array $where 查询条件
     * @param $user_long     用户经度
     * @param $user_lat      用户维度
     * @param int $limit 返回数量
     * @return mixed
     *
     */
    public function getStoresSelect($where = [], $user_long, $user_lat, $limit = 9)
    {
        return $this->merchantStoreModel->getStoresSelect($where, $user_long, $user_lat, $limit);
    }


    /**发现页top打卡店 所有团购商品店铺分类中 默认展示评分最高 且销量最高的店铺
     * @param array $where 查询条件
     * @param $user_long     用户经度
     * @param $user_lat      用户维度
     * @param int $limit 返回数量
     * @return mixed
     */
    public function getStoresTop($where = [], $user_long, $user_lat, $limit = 9)
    {
        return $this->merchantStoreModel->getStoresTop($where, $user_long, $user_lat, $limit);
    }

    /**根据用户经纬度获取最近一家店铺信息
     * @param array $where 查询条件
     * @param float $lng 用户经度
     * @param float $lat 用户维度
     * @param int $limit 返回的列表数量
     * @return mixed
     *
     */
    public function getUserDistance($where = [], $lng, $lat, $order = 's.sort DESC,s.store_id DESC', $page = 0, $pageSize = 10)
    {
        return $this->merchantStoreModel->getUserDistance($where, $lng, $lat, $order, $page, $pageSize);
    }

    /**
     * @param $where
     * @param $field
     * @param $order
     * @param $page
     * @param $pageSize
     * @return mixed
     * 根据条件查找店铺列表
     */
    public function getStoreByWhereList($where, $field, $order, $page, $pageSize)
    {
        $store_list = $this->merchantStoreModel->getStoreByWhereList($where, $field, $order, $page, $pageSize);
        if (!empty($store_list['list'])) {
            foreach ($store_list['list'] as $k => $v) {
                $store_list['list'][$k]['store_status'] = $this->getStoreStatus($v['end_time']);
                $store_list['list'][$k]['price_status'] = 1;
                $price_status = (new ClassPriceService())->getStoreCategoryPriceDetail($v['cat_id'], $v['cat_fid'], $v['province_id']);
                if (empty($price_status)) {
                    $store_list['list'][$k]['price_status'] = 0;
                }
                if (!empty($v['last_time'])) {
                    $store_list['list'][$k]['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
                }
                if (!empty($v['end_time'])) {
                    $store_list['list'][$k]['end_time'] = date('Y-m-d H:i:s', $v['end_time']);
                }
            }
        }
        return $store_list;
    }

    /**
     * 根据店铺到期时间获得店铺状态
     * @param $time
     * @return mixed
     * status 0过期 1即将过期 2正常
     */
    public function getStoreStatus($time)
    {

        $left_time = $time - time();
        if ($left_time > 0) {// 未过期
            $mon_time = 30 * 86400;
            if ($left_time < $mon_time) {// 即将过期
                $status = 1;
            } else {// 进行中
                $status = 2;
            }
        } else {// 已过期
            $status = 0;
        }
        return $status;
    }

    /**
     * 根据店铺到期时间状态获得店铺状态的描述
     * @param $time
     * @return mixed
     * status 0过期 1即将过期 2正常
     */
    public function getStoreStatusStr($status)
    {
        $statusArr = [
            0 => L_('已过期'),
            1 => L_('即将过期'),
            2 => L_('使用中'),
        ];

        $statusStr = $statusArr[$status] ?? '未知';
        return $statusStr;
    }

    /**
     * 获取综合店铺详情信息
     * @param $storeId
     * @author: 张涛
     * @date: 2021/05/10
     */
    public function getIndexBaseInfo($storeId, $uid, $lat = '', $lng = '')
    {
        if ($storeId < 1) {
            throw new Exception(L_('店铺参数有误'), 1001);
        }

        //获取店铺基本信息
        $storeInfo = $this->getStoreInfo($storeId);
        if (empty($storeInfo)) {
            throw new Exception(L_('店铺不存在'), 1001);
        }
        if ($storeInfo['status'] != 1) {
            throw new Exception(L_('店铺不存在'), 1001);
        }

        $businessTime = (new MerchantStoreOpenTimeService())->getShowTimeByStore($storeId);
        $timeArr = [];
        $todayOpenTime = '';

        foreach ($businessTime as $t) {
            $timeArr[] = $t['week_show'] . ' ' . $t['time_show'];
            if ($t['current']) {
                $todayOpenTime = $t['time_show'];
            }
        }
        $todayOpenTime = count($businessTime) == 1 ? $businessTime[0]['time_show'] : $todayOpenTime;

        $phones = array_values(array_filter(explode(' ', $storeInfo['phone'])));
        $areaName = (new \app\common\model\service\AreaService())->getOne(['area_id' => $storeInfo['area_id']]);
        $rs = [
            'mer_id' => $storeInfo['mer_id'],
            'store_id' => $storeId,
            'store_name' => $storeInfo['name'],
            'store_supplier_info' => replace_file_domain_content(htmlspecialchars_decode($storeInfo['supplier_info'])),
            'store_stars' => $storeInfo['score'] > 0 ? $storeInfo['score'] : '5.0',
            'area' => $areaName['area_name'] ?? '',
            'address' => $storeInfo['adress'],
            'phone' => $phones,
            'lng' => $storeInfo['long'],
            'lat' => $storeInfo['lat'],
            'per_capita' => $storeInfo['permoney'] > 0 ? cfg('Currency_symbol') . $storeInfo['permoney'] : '',
            'background_style' => $storeInfo['main_status'],
            'group_goods_style' => $storeInfo['group_status'],
            'course_goods_style' => $storeInfo['class_status'],
            'business_license' => [
                'id_number' => $storeInfo['id_number'] ?: '', //证件号码
                'company_name' => $storeInfo['company_name'] ?: '', //企业名称
                'company_address' => $storeInfo['company_address'] ?: '', //地址
                'legal_person' => $storeInfo['legal_person'] ?: '', //法人
                'validity_term' => $storeInfo['validity_term'] ?: '', //有效期
                'business_range' => $storeInfo['business_range'] ?: '', //经营范围
                'id_image' => replace_file_domain($storeInfo['id_image']), //营业执照
            ],
            'imgs' => $storeInfo['pic_info_arr'],
            'video' => $storeInfo['video_url'] ? [replace_file_domain($storeInfo['video_url'])] : [],
            'video_list'=>[],
            'business_info' => [
                'is_business' => $storeInfo['is_business_open'],
                'business_time' => "营业时间:{$todayOpenTime}",
                'time_arr' => $timeArr
            ],
            'top_img' => replace_file_domain($storeInfo['top_img'] ?? ''), //店铺主页头部图片
            'supplier_info' => replace_file_domain_content_img(htmlspecialchars_decode($storeInfo['supplier_info'] ?? '')), //店铺详情
            'have_group'=> $storeInfo['have_group'],
            'have_meal'=> $storeInfo['have_meal'],
            'have_shop'=> $storeInfo['have_shop'],
            'have_mall'=> $storeInfo['have_mall']
        ];
        if ($storeInfo['video_url']) {
            $rs['video_list'][] = ['url' => replace_file_domain($storeInfo['video_url']), 'preview' => replace_file_domain($storeInfo['video_url_preview']??'')];
        }

        //纠错地址、查看资质
        $rs['business_license_url'] = '';
        $rs['infomation_correct_url'] = get_base_url('pages/store/v1/infomationCorrect/index?store_id=' . $storeId . '&store_name=' . $storeInfo['name'], true);
        if ($storeInfo['id_number']) {
            $rs['business_license_url'] = get_base_url('pages/store/v1/businessLicense/index?store_id=' . $storeId, true);
        }


        //其他门店数量
        $rs['store_count'] = $this->getCount([['store_id', '<>', $storeId], ['status', '=', 1], ['mer_id', '=', $storeInfo['mer_id']]]);

        //附近店铺，距离最近的10家店铺
        if(cfg('show_nearby_store')){
            $rs['store_nearby'] = $this->getStoreNearby($lat, $lng, $storeInfo['city_id'], [$storeId], 10);
        }else{
            $rs['store_nearby'] = [];
        }
        //移除员工专区店铺
        $setEmployeeCardMerIdAry = cfg('set_employee_card_mer_id') ? explode(',',cfg('set_employee_card_mer_id')) : [];
        if($setEmployeeCardMerIdAry && $rs['store_nearby'] && count($rs['store_nearby'])){
            foreach($rs['store_nearby'] as $key => $val){
                if(in_array(intval($val['mer_id']),$setEmployeeCardMerIdAry)){
                    unset($rs['store_nearby'][$key]);
                }
            }
            $rs['store_nearby'] = array_values($rs['store_nearby']);
        }

        //用户是否收藏、距离
        $isCollect = false;
        $distance = 0;
        if ($uid > 0) {
            $isCollect = (new MerchantUserRelationService())->isCollect($uid, $storeId);
        }
        if ($lng > 0 && $lat > 0 && $storeInfo['lat'] > 0 && $storeInfo['long'] > 0) {
            // $location2 = (new longLat())->gpsToBaidu($storeInfo['lat'], $storeInfo['long']);//转换腾讯坐标到百度坐标
            $distance = get_distance($storeInfo['lat'], $storeInfo['long'], $lat, $lng);
        }
        $rs['is_collect'] = $isCollect;
        $rs['distance'] = $distance > 0 ? get_range($distance, false, false) : '';

        //分享文案
        $shareWx = (cfg('pay_wxapp_important') && cfg('pay_wxapp_username')) ? 'wxapp' : 'h5';
        $shareUrl = get_base_url('/pages/store/v1/home/index?store_id=' . $storeId, true);
        $shareImage = $storeInfo['share_image'] ? replace_file_domain($storeInfo['share_image']) : $storeInfo['pic_info_arr'][0] ?? '';
        $shareTitle = $storeInfo['share_title'] ?: $storeInfo['name'];
        $rs['share_wx'] = $shareWx;
        if ($shareWx == 'wxapp') {
            $rs['share_info'] = [
                'user_name' => cfg('pay_wxapp_username'),
                'path' => '/pages/plat_menu/index?redirect=webview&webview_url=' . urlencode($shareUrl) . '&webview_title=' . urlencode($storeInfo['name']),
                'img' => $shareImage,
                'title' => $shareTitle,
                'url' => $shareUrl
            ];
        } else {
            $rs['share_info'] = [
                'title' => $shareTitle,
                'desc' => $storeInfo['share_subtitle'] ?: L_('点击查看更多店铺精彩推荐'),
                'img' => $shareImage,
                'url' => $shareUrl
            ];
        }

        //业务按钮
        $rs['btns'] = [];
        $rs['btn_slider'] = [];// 包含自定义导航
        if ($storeInfo['have_shop']) {
            $shopInfo = (new MerchantStoreShopService)->getStoreByStoreId($storeId);
            if ($shopInfo) {
                $rs['btn_slider'][] = $rs['btns'][] = [
                    'name' => cfg('shop_alias_name'),
                    'desc' => '正在接单',
                    'url' => get_base_url('/pages/shop_new/shopDetail/shopDetail?store_id=' . $storeId),
                    'pic' => cfg('site_url') . '/static/images/store/shop_deliver-2.png',
                ];
            }
        }
        if ($storeInfo['have_meal'] && cfg('open_meal_v2')) {
            $foodshopInfo = (new MerchantStoreFoodshopService)->getStoreByStoreId($storeId);
            if ($foodshopInfo && $foodshopInfo['is_book']) {
                if ($foodshopInfo['book_type'] == 1) {
                    $url = get_base_url('pages/foodshop/order/onLineBookSeat?store_id=' . $storeInfo['store_id'] . '&store_name=' . $storeInfo['name']);
                } else {
                    $url = get_base_url('pages/foodshop/store/storeMenu?order_from=3&scan=0&store_id=' . $storeInfo['store_id'] . '&store_name=' . $storeInfo['name']);
                }
                $bookingCount = (new DiningOrderService())->getCount([['store_id', '=', $storeId], ['is_temp', '=', 0], ['order_from', 'in', [0, 3]]]);
                $rs['btn_slider'][] = $rs['btns'][] = [
                    'name' => L_('订座'),
                    'desc' => $bookingCount > 0 ? '快人一步（已订' . $bookingCount . '）' : '',
                    'url' => $url,
                    'pic' => cfg('site_url') . '/static/images/store/dining_book.png',
                ];
            }
        }

        //商家会员
		$merchantCard = (new CardNewService())->getCardByMerId($storeInfo['mer_id']);
		$haveCard = ($merchantCard && $merchantCard['self_get'] == 1 && $merchantCard['status'] == 1) ? true : false;
		if($haveCard){
            $rs['btn_slider'][] = [
                'name' => L_('会员卡'),
                'desc' => '',
                'url' => cfg('site_url') . '/wap.php?c=My_card&a=merchant_card&mer_id='.$storeInfo['mer_id'],
                'pic' => cfg('site_url') . '/static/images/store/merchant_card.png',
            ];
		}

        // 自定义导航
        $slider = $this->getStoreSliderSome(['status'=>1,'store_id'=>$storeId],'*', ['sort'=>'desc','id'=>'desc'],0,0);
        foreach ($slider as$value) {
            $rs['btn_slider'][] = [
                'url' => $value['url'],
                'name' => $value['name'],
                'pic' => replace_file_domain($value['pic']),
            ];
        }

        //快速买单
        if (cfg('pay_in_store') && (is_null(cfg('show_pay_in_store')) || cfg('show_pay_in_store') == 1)) {
            $discountTxt = '';
            $storeDiscount = $storeInfo['discount_txt'] ? unserialize($storeInfo['discount_txt']) : [];
            if ($storeDiscount && is_array($storeDiscount)) {
                $discountType = $storeDiscount['discount_type'] ?? 0;
                if ($discountType == 1 && $storeDiscount['discount_percent'] > 0 && $storeDiscount['discount_percent'] < 10) {
                    //折扣
                    $discountTxt = L_('X1折', ['X1' => $storeDiscount['discount_percent']]);
                } else if ($discountType == 2 && $storeDiscount['minus_price'] > 0) {
                    //满减
                    $discountTxt = L_('满X1减X2', ['X1' => $storeDiscount['condition_price'], 'X2' => $storeDiscount['minus_price']]);
                }
            }

            $rs['pay_in_store'] = [
                'show' => true,
                'discount' => $discountTxt,
                'url' => cfg('site_url') . '/wap.php?c=My&a=pay&store_id=' . $storeId
            ];
        } else {
            $rs['pay_in_store'] = ['show' => false];
        }

        //抖音环境暂时屏蔽掉业务和优惠买单
        if (request()->agent == 'douyin_mini') {
            $rs['btns'] = $rs['btn_slider'] = [];
            $rs['pay_in_store'] = ['show' => false];
        }

        //优惠券列表
        $couponLists = [];
        $merCoupons = (new MerchantCouponService())->getMerchantCouponList($storeInfo['mer_id'], 0, 'group', '', $uid, true,$storeId);
        $showTitle = [];
        if ($merCoupons) {
            foreach ($merCoupons as $mc) {
                $orderMoney = get_format_number($mc['order_money']);
                $discount = get_format_number($mc['discount']);
                $couponLists[] = [
                    'coupon_id' => $mc['coupon_id'],
                    'name' => $mc['cate_ch_name'],
                    'order_money' => $orderMoney,
                    'discount' => $discount,
                    'end_date' => date('Y-m-d', $mc['end_time']),
                    'is_get' => $mc['is_get'],
                    'is_use' => $mc['is_use']
                ];;
                count($showTitle) < 2 && $showTitle[] = L_('满X1减X2', ['X1' => $orderMoney, 'X2' => $discount]);
            }
        }
        $rs['coupons'] = [
            'tag' => '券',
            'title' => implode(' ', $showTitle),
            'lists' => $couponLists
        ];

        //以下团购其他数据
        $groupService = new GroupService();

        //代金券
        $rs['cashing'] = $groupService->getCashingGoodsByStoreId($storeId);

        //预约礼
        $appintGift = (new GroupAppointService())->getStoreAppointGift($storeId);
        $rs['appoint_gift'] = [
            'show' => ($appintGift && $appintGift['gift']) ? true : false,
            'gift' => $appintGift ? explode(',', $appintGift['gift']) : []
        ];

        //精品团购，推荐到店铺主页的团购商品
        $rs['recom_group'] = $groupService->getGoodsByStoreId($storeId, 'normal', true, 'g.pin_num = 0', 3);

        //拼团中的商品
        $team = $groupService->getCurrentPinRecordByStoreId($storeId);
        empty($team['info']) && $team['info'] = new \stdClass();
        $rs['team'] = $team;

        //场次预定
        $rs['booking_appoint'] = $groupService->getBookingAppointByStoreId($storeId);

        //获取标签
        $rs['labels'] = $groupService->getLabelsWithGoodsByStoreId($storeId);

        //团购商品展示、课程预约
        $rs['normal_goods'] = [];
        $rs['course_goods'] = [];
        if($storeInfo['have_group']){
            $labelGoods = $groupService->getStoreGoupGoodsByLabel($storeId, 0, 0,'index');
            $rs['normal_goods'] = $labelGoods['normal_goods'] ?? [];
            $rs['course_goods'] = $labelGoods['course_goods'] ?? [];
        }

        if (empty($rs['labels']) && ($rs['normal_goods'] || $rs['course_goods'])) {
            $rs['labels'][0] = ["label_id" => 0, "mer_id" => $storeInfo['mer_id'], "fid" => 0, "name" => L_('全部'), "is_del" => 0, "children" => []];
        }

        //评论
        $where = [
            ['r.store_id', '=', $storeId],
            ['r.is_del', '=', 0],
            ['r.status', '<>', 2]
        ];
        

        if (cfg('open_shop_reply_check')) { //开启快店评论审核功能
            $where[] = \think\facade\Db::raw("(r.`order_type` = 3 AND r.`check`=2) or (r.`order_type` != 3)");
        }
        if (cfg('open_mall_reply_check')) { //开启商城评论审核功能
            $where[] = \think\facade\Db::raw("(r.`order_type` = 6 AND r.`check`=2) or (r.`order_type` != 6)");
        }

        $order = 'r.is_good DESC,r.score DESC,r.pigcms_id DESC';
        $rs['reply'] = (new ReplyService())->getReplyLists($where, $order, 2);

        if($uid){
            //是否可以回复评论
            foreach($rs['reply']['lists'] as $key => $val){
                $can_reply = 0;
                if($val['uid'] == $uid && is_null($val['user_reply']) && !is_null($val['merchant_reply'])){
                    $can_reply = 1;
                }
                $rs['reply']['lists'][$key]['can_reply'] = $can_reply;
            }
        }

        //问大家
        $rs['open_qa'] = intval(cfg('open_qa'));
        if ($rs['open_qa'] != 1) {
            $rs['asks'] = [];
        } else {
            $rs['asks'] = (new AskService())->getAskList(['page' => 1, 'page_size' => 2, 'store_id' => $storeId], 'index_show DESC,reply_count DESC,id DESC', false);
        }
        $rs['asks_url'] = get_base_url('pages/store/v1/question/list/index?store_id=' . $storeId, true);
        //增加浏览数
        (new MerchantStore())->where(['store_id' => $storeId])->inc('hits', 1)->update();
        return $rs;
    }

    /**
     * 获取店铺列表右侧标识
     */
    public function getBusLabel($store)
    {
        $returnArr = [];
        // 排号
        if (isset($store['is_queue']) && $store['is_queue'] == 1) {
            $returnArr[] = [
                'type' => 'queue',
                'name' => '排',
            ];
        }

        // 外卖
        // 外卖优惠
        // if ($store['shop_on_sale'] == 1 && $store['have_shop'] == 1) {
        //     $returnArr[] = [
        //         'type' => 'shop',
        //         'name' => '外',
        //     ];
        // }

        // 预订
        if (isset($store['is_book']) && $store['is_book'] == 1) {
            $returnArr[] = [
                'type' => 'book',
                'name' => '订',
            ];
        }

        // 快速买单

        // if (cfg('pay_in_store') && $store['discount_txt']) {
        //     $returnArr[] = [
        //         'type' => 'check',
        //         'name' => '买',
        //     ];
        // }

        return array_values($returnArr);
    }

    /**
     * 获取附近店铺
     * @author: 张涛
     * @date: 2021/05/24
     */
    public function getStoreNearby($lat, $lng, $cityId = 0, array $except = [], $limit = 0)
    {
        $where = [['a.status', '=', 1]];
        if ($except && is_array($except)) {
            $where[] = ['a.store_id', 'not in', $except];
        }
        if ($cityId) {
            $where[] = ['a.city_id', '=', $cityId];
        }
        
        $fields = 'a.*';
        if ($lat && $lng) {
            $order = 'distance ASC';
            $fields .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-a.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(a.`lat`*PI()/180)*POW(SIN(({$lng}*PI()/180-a.`long`*PI()/180)/2),2)))*1000) AS distance";
        } else {
            $order = 'a.store_id DESC';
        }
        $data = $this->merchantStoreModel->alias('a')->field($fields)->join('merchant b','a.mer_id = b.mer_id and b.status = 1')->where($where)->order($order)->limit($limit)->select()->toArray();
        $res = $this->formatData($data);
        return $res;
    }

    public function getStoresByIds($ids)
    {
        $merchantStore = $this->merchantStoreModel->getStoresByIds($ids);
        if (!$merchantStore) {
            return [];
        }

        return $merchantStore->toArray();
    }

    /**
     * 经营资质
     * @date: 2021/06/01
     */
    public function storeLicense($storeId)
    {
        //获取店铺基本信息
        $storeInfo = $this->getStoreInfo($storeId);
        if (empty($storeInfo)) {
            throw new Exception(L_('店铺不存在'), 1003);
        }

        $authFiles = [];
        if (!empty($storeInfo['auth_files']) && $storeInfo['auth'] == MerchantStore::AUTH_SUCCESS) {
            $tmpPicArr = explode(';', $storeInfo['auth_files']);
            foreach ($tmpPicArr as $key => $value) {
                if (strpos($value, ',') !== false) {
                    $value = strtr($value, ',', '/');
                }
                $authFiles[] = replace_file_domain($value);
            }
        }
        $authArr = [
            'auth'       => $storeInfo['auth'],
            'reason'     => $storeInfo['reason'],
            'auth_files' => $authFiles,
        ];

        $license = [
            'id_number' => $storeInfo['id_number'], //证件号码
            'company_name' => $storeInfo['company_name'], //企业名称
            'company_address' => $storeInfo['company_address'], //地址
            'legal_person' => $storeInfo['legal_person'], //法人
            'validity_term' => $storeInfo['validity_term'], //有效期
            'business_range' => $storeInfo['business_range'], //经营范围
            'id_image' => replace_file_domain($storeInfo['id_image']), //营业执照
            'auth' => $authArr, //营业资质
        ];
        return $license;
    }

    /**
     * 上报纠错信息
     * @date: 2021/06/01
     */
    public function saveReportError($uid, $storeId, $pics, $content)
    {
        if (empty($content)) {
            throw new Exception(L_('请输入纠错信息'));
        }
        $pics = array_filter($pics);
        if (empty($pics) || !is_array($pics)) {
            throw new Exception(L_('请上传纠错图片'));
        }
        $store = $this->getOne(['store_id' => $storeId]);
        if (empty($store)) {
            throw new Exception(L_('店铺不存在'));
        }
        $data = [
            'mer_id' => $store['mer_id'],
            'store_id' => $storeId,
            'uid' => $uid,
            'pic' => implode(';', $pics),
            'content' => $content,
            'add_time' => time(),
            'status' => 0
        ];
        $id = (new MerchantCorrService())->add($data);
        return $id;
    }

    //商家店铺详情
    public function getMerStoreList($where, $field)
    {
        $list = $this->merchantStoreModel->where($where)->field($field)->select()->toArray();
        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['store_type_name'] = '';
                $list[$k]['cat_fname'] = (new MerchantCategory())->getOneData(['cat_id' => $v['cat_id']])['cat_name'] ?? '';
                $list[$k]['cat_name'] = (new MerchantCategory())->getOneData(['cat_id' => $v['cat_fid']])['cat_name'] ?? '';
                $list[$k]['store_type_name'] = $list[$k]['cat_fname'] . '-' . $list[$k]['cat_name'];
                $list[$k]['add_time'] = $v['add_time'] ? date('Y-m-d H:i:s', $v['add_time']) : '无';
                $list[$k]['status_value'] = '';
                $list[$k]['status'] = $this->getStoreStatus($v['effect_time']);//状态
                $list[$k]['status_value'] = $this->getStoreStatusStr($list[$k]['status']);//状态描述
                $list[$k]['total_money_sum'] = invoke_cms_model('Order/sell_order_date', [0, ['store_list' => $v['store_id'], 'type' => 'shop', 'pay_type' => 'all']])['retval']['total_money_sum'];
//                if ($v['effect_time'] <= time()) {
//                    $list[$k]['status'] = 3;//已过期
//                    $list[$k]['status_value'] = '已过期';
//                } else if ($v['effect_time'] <= time()+30*86400) {
//                    $list[$k]['status'] = 2;//即将过期
//                    $list[$k]['status_value'] = '即将过期';
//                } else {
//                    $list[$k]['status_value'] = '使用中';
//                }
                $list[$k]['effect_time'] = $v['effect_time'] ? date('Y-m-d H:i:s', $v['effect_time']) : '无';
            }
        }
        return $list;
    }

    //商家店铺详情 几个店铺即将过期
    public function getMerStoreInfo($where, $field)
    {
        $list = $this->merchantStoreModel->where($where)->field($field)->select()->toArray();
        $count1 = 0;//已过期数量
        $count2 = 0;//即将过期数量
        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['status_value'] = '';
                $list[$k]['status'] = $this->getStoreStatus($v['effect_time']);//状态
                $list[$k]['status_value'] = $this->getStoreStatusStr($list[$k]['status']);//状态描述
                if ($list[$k]['status'] == 0) {//已过期
                    $count1 += 1;
                } else if ($list[$k]['status'] == 1) {
                    $count2 += 1;
                }
            }
        }
        return ['count1' => $count1, 'count2' => $count2];
    }

    /**
     * @desc 获取店铺营业时间
     * @param $mer_id
     * @param $store_id
     * @param $api：接口暂时不支持显示到秒
     * @param $cut：如果不是开启的状态不显示当天的字段【根据status字段判断】
     * @return array
     */
    public function get_store_open_time($mer_id,$store_id,$api=0,$cut=false){
        if(!$mer_id || !$store_id) return array();
        $where = array();
        $where['mer_id'] = $mer_id;
        $where['store_id'] = $store_id;

        $order = 'week ASC,time_sort ASC';
       // $data = $this->where($where)->order($order)->select();
        $data =(new MerchantStoreOpenTime())->getSome(['mer_id'=>$mer_id,"store_id"=>$store_id],true,$order)->toArray();
        return $data?$this->deal_week_data($data,$api,$cut):$this->get_empty_data($api,$cut);
    }

    /**
     * @desc 以“周几”格式化数组->输出页面
     * @param $data
     * @param $api：接口暂时不支持显示到秒
     * @param $cut：如果不是开启的状态不显示当天的字段【根据status字段判断】
     * @return mixed
     */
    protected function deal_week_data($data,$api=false,$cut=false){
        if(!$data) return $this->get_empty_data($api,$cut);
        $data = $this->fill_data($data);//数据为空的时间段数据库不保存，这里需要填充下
        $week_show = array(L_('周日'),L_('周一'),L_('周二'),L_('周三'),L_('周四'),L_('周五'),L_('周六'));
        $return = array();
        foreach($data as $key => $val){
            if($api){
                $val['open_time']  = substr($val['open_time'],0,strlen($val['open_time'])-3);
                $val['close_time'] = substr($val['close_time'],0,strlen($val['close_time'])-3);
            }
            if((!$cut||$val['status']==1) && !empty($week_show[$val['week']])){
                $return[$val['week']]['week_show'] = $week_show[$val['week']];
                $return[$val['week']]['week_status'] = $val['status'];//这里的状态是针对周几的，所以每天的三个时间段状态必须一致，这里可以任取其一
                $return[$val['week']]['time_list'][] = $val;
                $return[$val['week']]['week'] = $val['week'];
            }

        }
        return $return;
    }

    /**
     * @desc 返回一个格式化好的空数组
     * @param $api：接口暂时不支持显示到秒
     * @param $cut：如果不是开启的状态不显示当天的字段【根据status字段判断】
     * @return array
     */
    protected function get_empty_data($api=false,$cut=false){
        if($cut) return array();
        $return = array();
        $week_show = array(L_('周日'),L_('周一'),L_('周二'),L_('周三'),L_('周四'),L_('周五'),L_('周六'));
        $sort = $this->sort;
        foreach($week_show as $key => $val){
            $temp = array();
            $temp['week_show']  = $val;
            $temp['week_status'] = 0;
            $temp['week'] = $key;
            $list = array();
            foreach($sort as $k => $v){
                $l = array();
                $l['open_time'] = $api?'00:00':'00:00:00';
                $l['close_time'] = $api?'00:00':'00:00:00';
                $l['time_sort'] = $v;
                $list[] = $l;
            }
            $temp['time_list'] = $list;
            $return[] = $temp;
        }
        return $return;
    }

    /**
     * @desc 填充数据
     * @param $data
     * @return array
     */
    protected function fill_data($data){
        for($i=0;$i<=6;$i++){
            for($j=1;$j<=count($this->sort);$j++){
                $is = false;//是否存在，不存在需要填充
                $status = 0;
                foreach($data as $key => $val){
                    $mer_id   = $val['mer_id'];
                    $store_id = $val['store_id'];
                    if($val['week']==$i){
                        $status = $val['status'];
                    }
                    if($val['week']==$i && $val['time_sort']==$j){
                        $is = true;
                    }
                }
                if(!$is){
                    $temp = array();
                    $temp['mer_id'] = $mer_id;
                    $temp['store_id'] = $store_id;
                    $temp['week'] = $i;
                    $temp['open_time'] = '00:00:00';
                    $temp['close_time'] = '00:00:00';
                    $temp['time_sort'] = $j;
                    $temp['status'] = $status;
                    $temp['type'] = 0;
                    $data[] = $temp;
                }

            }
        }
        return $data;
    }

    /**
     * 获取优惠买单返还记录
     */
    public function getCashBackList($params)
    {
        $cashBackModel = new MerchantStoreCashBackLog();
        
        $condition = $this->getCashBackCondition($params);

        $list = $cashBackModel->where($condition)->append(['add_time_text'])->order('add_time DESC')->paginate($params['page_size'])->toArray();

        $list['total_back_num'] = $cashBackModel->where($condition)->sum('cash_back_price');
        
        return $list;
    }

    /**
     * 导出优惠买单返还记录
     */
    public function exportCashBackList($params)
    {
        $cashBackModel = new MerchantStoreCashBackLog();
        
        $condition = $this->getCashBackCondition($params);

        $data = $cashBackModel->where($condition)->append(['add_time_text'])->order('add_time DESC')->select();


        $csvHead = array(
            L_('ID'),
            L_('店铺名称'),
            L_('支付金额'),
            L_('返还比例'),
            L_('返还金额'),
            L_('会员卡ID'),
            L_('日期')
        ); 
         
       
        $csvData = [];
        if (!empty($data)) { 
            foreach ($data as $key => $value) {
                $csvData[$key] = [
                    ' ' . $value['id'] . ' ',
                    $value['store_name'],
                    $value['pay_money'],
                    $value['cash_back_rate'] . '%',
                    $value['cash_back_price'],
                    $value['card_id'],
                    $value['add_time_text']
                ];
            }
        } 
        $filename = date("Y-m-d") . '-' . time() . '.csv'; 
        (new ExportService())->putCsv($filename, $csvData, $csvHead); 

        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl];
    }

    /**
     * 获取筛选条件
     */
    private function getCashBackCondition($params)
    {
        $condition = [];
        $condition[] = ['mer_id', '=', $params['mer_id']];
        // $condition[] = ['status', '=', 1];

        if(!empty($params['keywords'])){
            switch($params['search_by']){
                case 0: //店铺
                    $condition[] = ['store_name', 'like', "%{$params['keywords']}%"];
                    break;
                case 1: //会员卡
                    $condition[] = ['card_id', 'like', "%{$params['keywords']}%"];
                    break;
                case 2: //手机号
                    $userModel = new User();
                    $uids = $userModel->where([['phone', 'like', "%{$params['keywords']}%"]])->column('uid');
                    $condition[] = ['uid', 'in', $uids];
                    break;
            }
        }

        if(!empty($params['start_date']) && !empty($params['end_date'])){
            $condition[] = ['add_time', 'between', [strtotime($params['start_date']), strtotime($params['end_date'] . '23:59:59')]];
        }
        return $condition;
    }
}