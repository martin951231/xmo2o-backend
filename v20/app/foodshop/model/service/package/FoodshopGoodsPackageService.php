<?php
/**
 * 店铺套餐service
 * Created by subline.
 * Author: 钱大双
 * Date Time: 2020年12月14日17:17:14
 */

namespace app\foodshop\model\service\package;

use app\foodshop\model\service\package\FoodshopGoodsPackageDetailService;
use app\foodshop\model\service\goods\FoodshopGoodsLibraryService;
use app\foodshop\model\db\FoodshopGoodsPackage;

class FoodshopGoodsPackageService
{
    public $foodshopGoodsPackageModel = null;

    public function __construct()
    {
        $this->foodshopGoodsPackageModel = new FoodshopGoodsPackage();
    }

    /**根据条件返回套餐总数
     * @param $param  array 条件
     * @return mixed
     */
    public function getPackagerCount($param)
    {
        $condition = [];
        //店铺ID
        $condition[] = ['p.store_id', '=', $param['store_id']];
        // 搜索关键字
        if (isset($param['keyword']) && $param['keyword'] !== '') {
            $condition[] = ['p.name', 'like', '%' . $param['keyword'] . '%'];
        }

        //套餐状态
        $condition[] = ['p.status', '=', 1];

        if($param['user_type']=='user'){
            //用户是否可下单
            $condition[] = ['p.is_order', '=', 1];
        }

        //套餐商品明细
        $condition[] = ['d.goods_detail', '<>', ''];
        $count = $this->foodshopGoodsPackageModel->getPackageCountByJoin($condition);
        return $count;
    }

    /**根据条件返回套餐明细列表数据
     * @param $param  array 条件
     * @return mixed
     */
    public function getPackageDetailList($param, $filed = '*')
    {
        $condition = [];
        //店铺ID
        $condition[] = ['p.store_id', '=', $param['store_id']];
        // 搜索关键字
        if (isset($param['keyword']) && $param['keyword'] !== '') {
            $condition[] = ['p.name', 'like', '%' . $param['keyword'] . '%'];
        }

        //套餐状态
        $condition[] = ['p.status', '=', 1];

        if($param['user_type'] == 'user'){
            //用户是否可下单
            $condition[] = ['p.is_order', '=', 1];
        }

        //套餐商品明细
        $condition[] = ['d.goods_detail', '<>', ''];
        $list = $this->foodshopGoodsPackageModel->getPackageListByJoin($condition, $filed);
        return $list;
    }

    /**组装套餐分类数据
     * @param $type
     * @param $package_list
     */
    public function getPackageGoodsTree($type, $package_list)
    {
        if (empty($package_list)) {
            return [];
        }
        $package_list = array_column($package_list, NULL, 'id');

        // 多语言处理
        if (cfg('open_multilingual')) {
            $package_ids = array_unique(array_column($package_list, 'id'));
            $multilingual_package_name = $this->getSome($where = [['id', 'in', $package_ids]]);
            $multilingual_package_name = array_column($multilingual_package_name, NULL, 'id');
            foreach ($package_list as &$_package) {
                $_package['name'] = isset($multilingual_package_name[$_package['id']]['name']) ? $multilingual_package_name[$_package['id']]['name'] : $_package['name'];
            }
        }
        $data_list = [];
        $foodshopGoodsPackageDetailService = new FoodshopGoodsPackageDetailService();
        foreach ($package_list as $key => $value) {
            $arr['product_id'] = $value['id'];
            $arr['product_name'] = $value['name'];
            $arr['product_price'] = $value['price'];
            $arr['product_image'] = empty($value['image']) ? cfg('site_url') . '/tpl/Merchant/default/static/images/default_img.png' : cfg('site_url') . $value['image'];
            $where = [];
            $where[] = ['pid', '=', $value['id']];
            $where[] = ['goods_detail', '<>', ''];
            $package_detail = $foodshopGoodsPackageDetailService->getSome($where, '*', 'id asc', 1, 3);

            $packages = [];
            foreach ($package_detail as $val) {
                $package['id_sp'] = $val['id'];
                $package['pid_sp'] = $val['pid'];
                $package['name'] = $val['package_name'];
                $goods_ids = json_decode($val['goods_detail'], true);
                $goods_list = (new FoodshopGoodsLibraryService())->getGoodsList($where = ['goods_ids' => $goods_ids])['list'];
                $goods = [];
                foreach ($goods_list as $good) {
                    $g['product_id'] = $good['goods_id'];
                    $g['product_name'] = $good['name'];
                    $goods[] = $g;
                }
                $package['goods'] = $goods;
                $packages[] = $package;
            }
            $arr['subsidiary_piece'] = $packages;
            $arr['is_package_goods'] = true;
            $data_list[] = $arr;
        }

        $returnArr = [];
        if ($type == 'tree') {
            $returnArr[0]['cat_id'] = 'package';
            $returnArr[0]['cat_name'] = L_('精选套装');
            $returnArr[0]['desc'] = '';
            $returnArr[0]['goods_list'] = $data_list;
        } else {
            $returnArr = $data_list;
        }
        return $returnArr;
    }

    /**根据条件获取数据
     * @param $param
     * @return mixed
     */
    public function getPackageList($param)
    {
        $storeId = $param['store_id'] ?? 0;
        $storeIds = $param['store_ids'] ?? '';
        $pageSize = $param['pageSize'] ?? 10;
        $page = $param['page'] ?? 1;

        $where = [];
        if($storeId){
            $where[] =['store_id', '=', $storeId];
        }
        
        if(isset($param['store_ids'])){
            $where[] =['store_id', 'in', $storeIds];
        }

        if(isset($param['is_order'])){
            $where[] =['is_order', '=', $param['is_order']];
        }
        
        if(isset($param['status'])){
            $where[] =['status', '=', $param['status']];
        }
        
        $order = [
            'dateline' => 'desc'
        ];
        $list = $this->getSome($where, true, $order, $page, $pageSize);
        $count = $this->getCount($where);

        $foodshopGoodsPackageDetailService = new FoodshopGoodsPackageDetailService();
        $packageList = [];
        foreach ($list as $key => $value) {
            $arr['index'] = ++$key;
            $arr['id'] = $value['id'];
            $arr['store_id'] = $value['store_id'];
            $arr['name'] = $value['name'];
            $arr['price'] = $value['price'];
            $arr['status'] = $value['status'];
            $packageDetail = $foodshopGoodsPackageDetailService->getSome($where = ['pid' => $value['id']]);
            if (!empty($packageDetail)) {
                $arr['num'] = 0;
                foreach ($packageDetail as $val) {
                    if (!empty($val['goods_detail'])) {
                        $arr['num'] += count(json_decode($val['goods_detail'], true));
                    }
                }
            } else {
                $arr['num'] = 0;
            }
            $packageList[] = $arr;
        }

        $returnArr['list'] = $packageList;
        $returnArr['count'] = $count;
        return $returnArr;
    }

    /**保存或添加套餐
     * @param $param
     * @return bool
     * @throws \think\Exception
     */
    public function editPackage($param)
    {
        $id = $param['id'] ?? 0;
        $storeId = $param['store_id'] ?? 0;

        if (empty($storeId)) {
            throw new \think\Exception(L_("缺少店铺ID参数"), 1003);
        }

        if (empty($param['name'])) {
            throw new \think\Exception(L_("套餐名称必填"), 1003);
        }
        $image = '';
        if (isset($param['image']) && !empty($param['image'])) {
            $path = $param['image'];
            $pathArr = explode("/upload/", $path);
            $image = isset($pathArr[1]) ? '/upload/' . $pathArr[1] : '';
        }


        $data = [
            'name' => $param['name'] ?? '',
            'price' => $param['price'] ?? 0,
            'note' => $param['note'] ?? '',
            'status' => $param['status'] ?? 0,
            'image' => $image,
            'is_order' => $param['is_order'] ?? 1,
        ];

        if ($id > 0) {
            //修改
            $where = [
                'id' => $id
            ];

            $item = $this->getOne($where);
            if (empty($item)) {
                throw new \think\Exception("数据不存在");
            }

            $rs = $this->updateThis($where, $data);

        } else {
            $data['store_id'] = $storeId;
            $data['dateline'] = time();

            //新增
            $rs = $id = $this->add($data);
        }

        if ($rs === false) {
            if ($id) {
                throw new \think\Exception("修改失败");
            } else {
                throw new \think\Exception("添加失败");
            }
        }
        $returnArr = [];
        $returnArr['id'] = $id;
        return $returnArr;
    }

    /**根据id获取套餐信息
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    public function getDetail($param)
    {
        $id = $param['id'] ?? 0;
        if (!$id) {
            throw new \think\Exception(L_("缺少参数"), 1001);
        }

        //详情
        $where = [
            'id' => $id
        ];

        $detail = $this->getOne($where);
        if (empty($detail)) {
            throw new \think\Exception("数据不存在");
        }

        $returnArr = [];
        $returnArr['id'] = $detail['id'];
        $returnArr['store_id'] = $detail['store_id'];
        $returnArr['name'] = $detail['name'];
        $returnArr['price'] = $detail['price'];
        $returnArr['note'] = $detail['note'];
        $returnArr['status'] = $detail['status'];
        $returnArr['image'] = !empty($detail['image']) ? cfg('site_url') . $detail['image'] : '';
        $returnArr['is_order'] = $detail['is_order'];
        $return['detail'] = $returnArr;
        return $return;
    }

    /**删除套餐数据
     * @param $param
     * @return bool
     * @throws \think\Exception
     */
    public function delPackage($param)
    {
        $where = ['id' => $param['id']];
        (new FoodshopGoodsPackageDetailService())->delPackageDetail($param = ['pid' => $param['id']]);
        return $this->del($where);
    }

    /**
     * 根据条件一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where)
    {
        if (empty($where)) {
            return [];
        }

        $package = $this->foodshopGoodsPackageModel->getInfoByID($where);
        if (!$package) {
            return [];
        }

        return $package->toArray();
    }

    /**
     * 根据条件返回总数
     * @param $where array 条件
     * @return array
     */
    public function getCount($where = [])
    {
        $res = $this->foodshopGoodsPackageModel->getCount($where);
        if (!$res) {
            return 0;
        }
        return $res;
    }


    /**
     * 根据条件获取列表
     * @param $storeId
     * @return array
     */
    public function getSome($where = [], $field = true, $order = true, $page = 0, $limit = 0)
    {
        $res = $this->foodshopGoodsPackageModel->getPackageList($where, $field, $order, $page, $limit);

        if (!$res) {
            return [];
        }

        return $res->toArray();
    }


    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data)
    {
        $id = $this->foodshopGoodsPackageModel->insertGetId($data);
        if (!$id) {
            return false;
        }

        return $id;
    }

    /**
     * 更新数据
     * @param $where
     * @param $data
     */
    public function updateThis($where, $data)
    {
        $rs = $this->foodshopGoodsPackageModel->where($where)->update($data);

        if ($rs === false) {
            return false;
        }
        return true;
    }

    /**
     * 删除数据
     * @param $where
     * @param $data
     */
    public function del($where)
    {
        return $this->foodshopGoodsPackageModel->del($where);
    }
}