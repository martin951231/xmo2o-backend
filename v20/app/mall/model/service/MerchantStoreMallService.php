<?php
/**
 * MerchantStoreMallService.php
 * 店铺扩展表service
 * Create on 2020/9/22 17:11
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\common\model\service\CacheSqlService;
use app\mall\model\service\ExpressService;
use app\mall\model\db\MerchantStoreMall;
use app\merchant\model\db\MerchantStore;

class MerchantStoreMallService
{
    public function __construct()
    {
        $this->merchantStoreMallModel = new MerchantStoreMall();
    }

    /**
     * 完善店铺
     * @param $param
     */
    public function perfectedStore($param)
    {
        if (empty($param['store_id'])) {
            throw new \think\Exception('缺少store_id参数');
        }
        if(!$param['is_delivery']&&!$param['is_houseman']&&!$param['is_zt']){
            throw new \think\Exception('请至少选择一种配送方式！');
        }
        if (!empty($param['level_off'])) {
            foreach ($param['level_off'] as $kk => $vv) {
                $vv['type'] = intval($vv['type']);
                $newleveloff[$kk] = $vv;
            }
            $param['level_off'] = $newleveloff ? serialize($newleveloff) : '';
        }
        $param['stockup_time'] = implode(';', [$param['stockup_day'], $param['stockup_hour'], $param['stockup_minute']]);
        $param['single_face_info'] = empty($param['single_face_info']) ? '' : json_encode($param['single_face_info']);
        unset($param['stockup_day']);
        unset($param['stockup_hour']);
        unset($param['stockup_minute']);
        $where = ['store_id' => $param['store_id']];
        if ($this->merchantStoreMallModel->getOne($where)) {
            $result = $this->merchantStoreMallModel->updateThis($where, $param);
        } else {
            $result = $this->merchantStoreMallModel->add($param);
        }

        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return $result;
        }
    }

    /**
     * 获取配置列表
     * @param $store_id
     * @return array
     */
    public function getStoreConfigList($store_id)
    {
        if (empty($store_id)) {
            throw new \think\Exception('缺少store_id参数');
        }

        //基础店铺信息
        $merchantStoreService = new MerchantStoreService();
        $basic_arr = $merchantStoreService->getOne($store_id);
        $arr['store_name'] = $basic_arr['name'];
        $arr['pic_info'] = replace_file_domain(explode(';', $basic_arr['pic_info'])[0]);
        //快递公司列表
        $expressService = new ExpressService();
        $express_list = $expressService->getExpressList();
        foreach ($express_list as $key => $value) {
            $express_list[$key]['pic'] = dispose_url($value['pic'], '/upload/single_face/');
        }
        $arr['express_list'] = $express_list;
        //商城店铺信息
        $where = ['store_id' => $store_id];
        $field = 'store_notice,level_off,e_invoice_status,invoice_money,delivery_fee_type,new_order_warn,stockup_time,horseman_type,is_delivery,is_houseman,is_zt,device_id,single_face_info';
        $edit_arr = $this->merchantStoreMallModel->getStoreConfigList($where, $field);
        if (empty($edit_arr)) {
            $edit_arr = [
                'store_notice' => '',
                'level_off' => '',
                'e_invoice_status' => 0,
                'invoice_money' => 0,
                'delivery_fee_type' => 1,
                'new_order_warn' => 1,
                'stockup_time' => '0;0;0',
                'stockup_day' => 0,
                'stockup_hour' => 0,
                'stockup_minute' => 0,
                'horseman_type' => 0,
                'is_delivery' => 1,
                'is_houseman' => 0,
                'is_zt' => 1,
                'device_id' => '',
                'single_face_info' => []
            ];
        }
        if (cfg('mall_platform_delivery_open') == 0) {
            $edit_arr['is_houseman'] = 0;
        }
        $edit_arr['mall_platform_delivery_open'] = cfg('mall_platform_delivery_open');
        $pre_leveloff = [['lid' => 1, 'lname' => 'vip1', 'type' => 0, 'vv' => ''], ['lid' => 2, 'lname' => 'vip2', 'type' => 0, 'vv' => ''], ['lid' => 3, 'lname' => 'vip3', 'type' => 0, 'vv' => ''], ['lid' => 4, 'lname' => 'vip4', 'type' => 0, 'vv' => '']];
        $edit_arr['level_off'] = $edit_arr['level_off'] ? unserialize($edit_arr['level_off']) : $pre_leveloff;
        $edit_arr['level_off'][0]['txt'] = '白银级';
        $edit_arr['level_off'][1]['txt'] = '专业级';
        $edit_arr['level_off'][2]['txt'] = '尊享级';
        $edit_arr['level_off'][3]['txt'] = '至尊卡';
//        (new CacheSqlService())->clearCache();
        $edit_arr['is_platform_delivery_open'] = cfg('mall_platform_delivery_open');
        $edit_arr['single_face_info'] = empty($edit_arr['single_face_info']) ? [] : json_decode($edit_arr['single_face_info'], true);
        $time_arr = explode(';', $edit_arr['stockup_time']);
        if(!empty($time_arr)){
            $arr['stockup_day'] = isset($time_arr[0]) ? $time_arr[0] : 0;
            $arr['stockup_hour'] = isset($time_arr[1]) ? $time_arr[1] : 0;
            $arr['stockup_minute'] = isset($time_arr[2]) ? $time_arr[2] : 0;
            $arr = array_merge($arr, $edit_arr);
        }

        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    /**
     * 获取店铺列表
     * @author 钱大双
     * @date 2020/09/21
     */
    public function getStoremallStoreListByMerId($merId, $page = 1, $pageSize = 15)
    {
        $prefix = config('database.connections.mysql.prefix');
        $where = ['s.mer_id' => $merId, 's.have_mall' => 1, 's.status' => 1];
        $storeMod = new MerchantStore();
        $count = $storeMod->alias('s')
            ->join([$prefix . 'merchant_store_mall' => 'f'], 's.store_id=f.store_id', 'LEFT')
            ->join([$prefix . 'merchant' => 'm'], 'm.mer_id=s.mer_id')
            ->where($where)
            ->count();
        if ($count < 1) {
            $lists = [];
        } else {
            $fields = '`s`.`store_id`, `s`.`mer_id`, `s`.`name`, `s`.`adress`, `s`.`phone`, `s`.`sort`, `f`.`store_id` AS `sid`';
            $lists = $storeMod->alias('s')
                ->join([$prefix . 'merchant_store_mall' => 'f'], 's.store_id=f.store_id', 'LEFT')
                ->join([$prefix . 'merchant' => 'm'], 'm.mer_id=s.mer_id')
                ->field($fields)
                ->where($where)
                ->order('s.sort', 'DESC')
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->select()
                ->toArray();
        }
        $rs['list'] = $lists;
        $rs['total'] = $count;
        return $rs;
    }

    /**
     * 获取商城店铺信息
     * @param $store_id
     * @return array
     */
    public function getStoremallInfo($store_id, $field)
    {
        if (empty($store_id)) {
            return [];
        }
        $where = ['store_id' => $store_id];
        $info = $this->merchantStoreMallModel->getStoreConfigList($where, $field);
        return $info;
    }

    /**
     * 获取完整的店铺基本信息
     * @param  [type] $store_id [description]
     * @return [type]           [description]
     */
    public function getMallStoreInfo($store_id)
    {
        if (empty($store_id)) {
            return [];
        }
        $where = [
            ['s.store_id', '=', $store_id]
        ];
        $info = $this->merchantStoreMallModel->getFullInfo($where, 'm.*,s.*')->toArray();
        $info['logo'] = isset($info['logo']) ? replace_file_domain($info['logo']) : '';
        return $info;
    }
}