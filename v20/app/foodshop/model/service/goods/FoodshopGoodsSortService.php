<?php
/**
 * 餐饮商品分类service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/19 10:46
 */

namespace app\foodshop\model\service\goods;
use app\foodshop\model\db\FoodshopGoodsSort as FoodshopGoodsSortModel;
use app\merchant\model\service\MerchantStoreService;
use app\shop\model\service\goods\ShopGoodsService;

class FoodshopGoodsSortService {
    public $foodshopGoodsSortModel = null;
    public $weekList = [];
    public $unweekList = [];
    public function __construct()
    {
        $this->foodshopGoodsSortModel = new FoodshopGoodsSortModel();

        $this->weekList = [
            '1' => L_('星期一'),
            '2' => L_('星期二'),
            '3' => L_('星期三'),
            '4' => L_('星期四'),
            '5' => L_('星期五'),
            '6' => L_('星期六'),
            '0' => L_('星期日'),
        ];

        $this->unweekList = [
            L_('星期一') => '1',
            L_('星期二') => '2',
            L_('星期三') => '3',
            L_('星期四') => '4',
            L_('星期五') => '5',
            L_('星期六') => '6',
            L_('星期日') => '0',
        ];
    }
    /**
     * 获得商家后台分类列表
     * @param $where
     * @return array
     */
    public function getMerchantSortList($param, $merchantUser) {
        // 获得分类列表
        $where = [
//            'mer_id' => $merchantUser['mer_id'],
            'store_id' => $param['store_id'],
        ];
        $sortList = $this->getList($where);

        $condition = [];
        if(isset($param['order_status']) && $param['order_status']){
            switch ($param['order_status']){
                case '0'://全部商品
                    break;
                case '1'://售卖中
                    $condition[] = ['t.status','=', 1];
                    break;
                case '2'://已下架
                    $condition[] = ['t.status','=', 0];
                    break;
                case '3'://已售完
                    $condition[] = ['t.spec_stock|s.stock_num','=', 0];
                    break;
            }
        }

        // 返回前端需要格式
        $fomartList = [];
        foreach($sortList as $_sort){
            // 商品数量
            $where = [
                ['t.spec_sort_id', '=', $_sort['sort_id']],
                ['t.store_id', '=', $_sort['store_id']],
            ];
            $where = array_merge($where,$condition);
            $goodsCount = (new ShopGoodsService())->getGoodsCountByModule(FoodshopGoodsLibraryService::TABLE_NAME, $where);
            if($param['order_status']==0 || $goodsCount>0){
                $temp = [
                    'title' => $_sort['sort_name'],//分类名
                    'id' => $_sort['sort_id'],//分类id
                    'fid' => 0,//父id
                    'goods_count' => $goodsCount,//分类下商品总数
                    'children' => [],//子分类（餐饮只有一级分类）
                ];
                $fomartList[] = $temp;
            }
        }

        return $fomartList;
    }


    /**
     * 获得商家后台供选择的分类列表
     * @param $param
     * @return array
     */
    public function getSelectSortList($param, $merchantUser) {
        if(empty($param['store_id'])){
            throw new \think\Exception(L_("店铺id不存在"), 1001);
        }
        if(empty($merchantUser)){
            throw new \think\Exception(L_("商家不存在"), 1001);
        }
        $where['mer_id'] = $merchantUser['mer_id'];
        $where['store_id'] = $param['store_id'];
        $store = (new MerchantStoreService())->getOne($where);
        if(empty($store)){
            throw new \think\Exception(L_("店铺不存在"), 1001);
        }

        // 获得分类列表
        $where = [
            'store_id' => $param['store_id'],
        ];
        $sortList = $this->getList($where);

        return $sortList;
    }


    /**
     * 分类拖拽排序
     * @param $where
     * @return array
     */
    public function changeSort($sortList) {
//        var_dump($sortList);
        // 排序值
        $sort = 0;
        $sortList = array_reverse($sortList);
        foreach($sortList as $_sort){
            $sort += 10;
            // 条件
            $where = [
                'sort_id' => $_sort['id']
            ];
            // 更新排序值
            $data = [
                'sort' => $sort
            ];
            $res = $this->updateThis($where, $data);
        }

        return true;
    }

    /**
     * 获得分类详情
     * @param $param
     * @return array
     */
    public function geSortDetail($param) {
        $where['sort_id'] = $param['sort_id'];
        if(empty($param['sort_id'])){
            throw new \think\Exception(L_('参数错误'),1001);
        }
        $sort = $this->getOne($where);
//        var_dump($sort);
        $sort['show_start_date'] = $sort['show_start_date'] ? date('Y-m-d',$sort['show_start_date']) : '';
        $sort['show_end_date'] = $sort['show_end_date'] ? date('Y-m-d',$sort['show_end_date']) : '';
        if($sort['sort_discount'] > 0){
            $sort['sort_discount'] = $sort['sort_discount'] / 10;
        }

        $sort['week'] = $sort['week'] ? explode(',', $sort['week']) : '';
        if($sort['week']){
            foreach ($sort['week'] as &$_week){
                $_week= $this->weekList[$_week];
            }
        }
        return $sort;
    }

    /**
     * 编辑分类
     * @param $param
     * @return array
     */
    public function editSort($param) {
        $sortId = isset($param['sort_id']) ? $param['sort_id'] : '0';
        $param['show_start_date'] = $param['show_start_date'] ? strtotime($param['show_start_date']) : 0;
        $param['show_end_date'] = $param['show_end_date'] ? strtotime($param['show_end_date']) : 0;
        $param['show_start_time'] = $param['show_start_time'] ?? 0;
        $param['show_end_time'] = $param['show_end_time'] ?? 0;
        $param['show_start_time2'] = $param['show_start_time2'] ?? 0;
        $param['show_end_time2'] = $param['show_end_time2'] ?? 0;
        $param['show_start_time3'] = $param['show_start_tim3'] ?? 0;
        $param['show_end_time3'] = $param['show_end_time3'] ?? 0;

        if(!empty($param['sort_discount'])){

            if(!is_numeric($param['sort_discount']) || $param['sort_discount'] > 10 || $param['sort_discount'] < 0){
                throw new \think\Exception(L_("请输入正确的折扣率！"));
            }else{
                $param['sort_discount'] = $param['sort_discount'] == 10 ? 0 : floor($param['sort_discount'] * 10);
            }
        } 

        if(!$param['sort_name']){
            throw new \think\Exception(L_("请输入分类名称！"));
        }
        if(!$param['all_date'] && (!$param['show_start_date'] || !$param['show_end_date'])){
            throw new \think\Exception(L_("请填写自定义日期"));
        }
        if($param['show_start_time'] > $param['show_end_time']){
            throw new \think\Exception(L_("开启时间不能大于结束时间"));
        }
        if($param['show_start_time2'] > $param['show_end_time2']){
            throw new \think\Exception(L_("开启时间不能大于结束时间"));
        }
        if($param['show_start_time3'] > $param['show_end_time3']) {
            throw new \think\Exception(L_("开启时间不能大于结束时间"));
        }
        unset($param['date_range']);
        $weekArr = [];
        if($param['week']){
            foreach ($param['week'] as $week){
                $weekArr[] = $this->unweekList[$week];
            }
        }
        $param['week'] = implode(',',  $weekArr);
        if($sortId){
            //编辑
            $where = [
                'sort_id' =>$sortId
            ];
            $res = $this->updateThis($where, $param);
        }else{
            // 新增
            $res = $this->add($param);
        }
        if($res===false){
            throw new \think\Exception(L_("操作失败请重试"));

        }
        return true;
    }

    /**
     * 删除分类
     * @param $param
     * @return array
     */
    public function delSort($param) {
        $sortId = isset($param['sort_id']) ? $param['sort_id'] : '0';
        $storeId = isset($param['store_id']) ? $param['store_id'] : '0';
        $where = [
            'sort_id' => $sortId
        ];
        $nowSort = $this->getOne($where);
        if(empty($nowSort)){
            throw new \think\Exception(L_("分类不存在"),1003);
        }

        // 商品数量
        $whereGoods = [
            'sort_id' => $sortId
        ];
        $goodsCount = (new FoodshopGoodsLibraryService)->getGoodsList($whereGoods);

        if($goodsCount['list']){
            throw new \think\Exception(L_("该分类下有商品，先删除商品后再来删除该分类"),1003);
        }

        try {
            $result = $this->del($where);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }

        if($result===false){
            throw new \think\Exception(L_("操作失败请重试"),1003);

        }
        return true;
    }

    /**
     * 根据店铺ID返回在营业时间内的分类列表
     * @param $where
     * @return array
     */
    public function getShowSortListByStoreId($storeId) {
        $order = [
            'sort' => 'desc',
            'sort_id' => 'asc'
        ];
        // 分类列表
        $sortList = $this->foodshopGoodsSortModel->getSortListByStoreId($storeId, $order);

        if(!$sortList) {
            return [];
        }
        $sortList = $sortList->toArray();

        // 返回数组
        $returnArr = array(); 

        // 今日星期几
        $todayWeek = date('w');

        // 当前时间
        $time = time();
        foreach ($sortList as $value) {
            if(!$this->checkTime($value)){
                continue;
            }
            if ($value['week']){
                $weekArr = explode(',', $value['week']);
                $weekStr = '';
                foreach ($weekArr as $k => $v) {
                    $weekStr .=$this->weekList[$v] . ' ';
                }
                $value['weekStr'] = $weekStr;
            }
            $returnArr[] = $value;
        }
        return $returnArr; 
    }


    /**
     * 验证时间
     * @param $where
     * @return array
     */
    public function checkTime($data) {
        if(empty($data)){
            return false;
        }

        // 没有开启自定义时间段
        if($data['all_date'] == 1){
            return true;
        }

        // 验证日期
        $datatime = strtotime(date('Y-m-d'));
        if($datatime < strtotime(date('Y-m-d',$data['show_start_date'])) || $datatime > strtotime(date('Y-m-d',$data['show_end_date']))){
            return false;
        }

        // 验证星期几
        if (empty($data['week'])){
            // 没有设置代表不显示
            return false;
        }
        // 今日星期几
        $todayWeek = date('w');
        if($data['week'] === ''){
            return false;
        }

        $weekArr = explode(',', $data['week']);
        if(!in_array($todayWeek, $weekArr)){
            return false;
        }

        // 验证每日时间段（没有设置代表全时段）
        if ($data['all_time'] == 1) {
            return true;
        }

        $time = time();
        if($data['show_start_time'] != '00:00:00' || $data['show_end_time'] != '00:00:00'){
            // 时间段一
            $sTime = strtotime(date('Y-m-d ' . $data['show_start_time']));
            $eTime = strtotime(date('Y-m-d ' . $data['show_end_time']));
            if ($time >= $sTime && $time <= $eTime) {
                // 符合直接返回true，否则向下验证其他时间段
                return true;
            }

            if($data['show_start_time2'] != '00:00:00' || $data['show_end_time2'] != '00:00:00'){
                // 时间段二
                $sTime = strtotime(date('Y-m-d ' . $data['show_start_time2']));
                $eTime = strtotime(date('Y-m-d ' . $data['show_end_time2']));
                if ($time >= $sTime && $time <= $eTime) {
                    return true;
                }

                if($data['show_start_time3'] != '00:00:00' || $data['show_end_time3'] != '00:00:00'){
                    // 时间段三
                    $sTime = strtotime(date('Y-m-d ' . $data['show_start_time3']));
                    $eTime = strtotime(date('Y-m-d ' . $data['show_end_time3']));
                    if ($time >= $sTime && $time <= $eTime) {
                        return true;
                    }
                }
            }
        }elseif($data['show_start_time2'] != '00:00:00' || $data['show_end_time2'] != '00:00:00'){
            // 时间段二
            $sTime = strtotime(date('Y-m-d ' . $data['show_start_time2']));
            $eTime = strtotime(date('Y-m-d ' . $data['show_end_time2']));
            if ($time >= $sTime && $time <= $eTime) {
                return true;
            }

            if($data['show_start_time3'] != '00:00:00' || $data['show_end_time3'] != '00:00:00'){
                // 时间段三
                $sTime = strtotime(date('Y-m-d ' . $data['show_start_time3']));
                $eTime = strtotime(date('Y-m-d ' . $data['show_end_time3']));
                if ($time >= $sTime && $time <= $eTime) {
                    return true;
                }
            }
        }elseif($data['show_start_time3'] != '00:00:00' || $data['show_end_time3'] != '00:00:00'){
            // 时间段三
            $sTime = strtotime(date('Y-m-d ' . $data['show_start_time3']));
            $eTime = strtotime(date('Y-m-d ' . $data['show_end_time3']));
            if ($time >= $sTime && $time <= $eTime) {
                return true;
            }
        }

        return false;
    }
    /**
     * 将分类与商品组装
     * @param $where
     * @return array
     */
    public function getSortGoodsTree($sortList, $goodsList, $typeInfo = []) {
        if(empty($sortList) || empty($goodsList)){
            return [];
        }
        $returnGoodsList = array();
        foreach($goodsList as $_good){
            $returnGoodsList[$_good['spec_sort_id']][] = $_good;
        }

        $returnArr = array();

        foreach ($sortList as $k => $_sort) {
            $temp = [];
            $temp['sort_discount'] = $_sort['sort_discount'] / 10;
            $temp['cat_id'] = $_sort['sort_id'];
            $temp['cat_name'] = html_entity_decode($_sort['sort_name'],ENT_QUOTES);
            $temp['desc'] = $_sort['desc'] ?? '';

            if (isset($returnGoodsList[$_sort['sort_id']]) && $returnGoodsList[$_sort['sort_id']]) {
                $temp['goods_list'] = $returnGoodsList[$_sort['sort_id']];
                $returnArr[] = $temp;
            }
        }
        return $returnArr; 
    }

    /**
     * 根据店铺ID返回该店铺的分类列表
     * @param $where
     * @return array
     */
    public function getList($where,$order=[]) {
        if(empty($order)){
            // 排序
            $order = [
                'sort' => 'DESC',
                'sort_id' => 'ASC',
            ];
        }

        // 根据商品id查询
        if(isset($where['sort_id']) && $where['sort_id']){
            $where[] = ['sort_id','in', $where['sort_id']];
        }

        $sortList = $this->foodshopGoodsSortModel->getList($where,$order);
        if(!$sortList) {
            return [];
        }

        return $sortList->toArray();
    }

    /**
     * 根据店铺ID返回该店铺的分类列表
     * @param $where
     * @return array
     */
    public function getSortListByStoreId($storId,$order=[]) {
        if(empty($order)){
            // 排序
            $order = [
                'sort' => 'DESC',
                'sort_id' => 'ASC',
            ];
        }

        $sortList = $this->foodshopGoodsSortModel->getSortListByStoreId($storId,$order);
        if(!$sortList) {
            return [];
        }

        return $sortList->toArray();
    }


    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        $detail = $this->foodshopGoodsSortModel->getOne($where);
        if(!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 添加数据
     * @param $where array 条件
     * @return array
     */
    public function add($data) {
        if( empty($data)){
            return false;
        }

        $result = $this->foodshopGoodsSortModel->add($data);
        if(!$result) {
            return false;
        }

        return $this->foodshopGoodsSortModel->id;
    }

    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->foodshopGoodsSortModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }

    /**
     * 删除数据
     * @param $where array
     * @return array
     */
    public function del($where) {
        if(empty($where)){
            return false;
        }

        $result = $this->foodshopGoodsSortModel->where($where)->delete();
        if(!$result) {
            return false;
        }

        return $result;
    }

}