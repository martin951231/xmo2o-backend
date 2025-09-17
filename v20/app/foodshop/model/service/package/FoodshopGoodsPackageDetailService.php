<?php
/**
 * 店铺套餐service
 * Created by subline.
 * Author: 钱大双
 * Date Time: 2020年12月14日17:17:14
 */

namespace app\foodshop\model\service\package;

use app\foodshop\model\service\package\FoodshopGoodsPackageService;
use app\foodshop\model\service\goods\FoodshopGoodsLibraryService;
use app\shop\model\service\goods\GoodsImageService as GoodsImageService;
use app\shop\model\service\goods\ShopGoodsService as ShopGoodsService;
use app\foodshop\model\service\goods\FoodshopGoodsSortService;

use app\foodshop\model\db\FoodshopGoodsPackageDetail as FoodshopGoodsPackageDetailModel;

class FoodshopGoodsPackageDetailService
{
    const TABLE_NAME = 'foodshop_goods_library';
    public $foodshopGoodsPackageDetailModel = null;

    public function __construct()
    {
        $this->foodshopGoodsPackageDetailModel = new FoodshopGoodsPackageDetailModel();

    }


    /**
     * 根据套餐ID获得套餐商品详情
     * @param $param
     * @param $isWap 是否用户端
     * @return array
     */
    public function getPackageDetailByPid($param, $plat = 'wap')
    {
        $pid = $param['pid'] ?? 0;
        $package_info = (new FoodshopGoodsPackageService())->getOne($where = ['id' => $pid]);
        if (empty($package_info)) {
            throw new \think\Exception("套餐不存在");
        }

        $where = [
            ['pid', '=', $pid],
            ['goods_detail', '<>', '']
        ];
        $order = [
            'id' => 'asc'
        ];
        $package_detail = $this->getSome($where, true, $order);
        $packages = [];
        $goodsImageService = new GoodsImageService();
        $foodshopGoodsLibraryService = new FoodshopGoodsLibraryService();
        foreach ($package_detail as $val) {
            $package['id_sp'] = $val['id'];
            $package['pid_sp'] = $val['pid'];
            $package['name'] = $val['package_name'];
            $package['mininum'] = $val['num'];
            $package['maxnum'] = $val['num'];
            $choose_goods_ids = !empty($val['goods_detail_choose']) ? json_decode($val['goods_detail_choose'], true) : [];
            $goods_ids = json_decode($val['goods_detail'], true);
            $goods_list = (new FoodshopGoodsLibraryService())->getGoodsList($where = ['goods_ids' => $goods_ids])['list'];
            $goods = [];
            $ids = [];
            if (!empty($choose_goods_ids) && !empty($goods_ids)) {
                $ids = array_intersect($choose_goods_ids, $goods_ids);
            }
            foreach ($goods_list as $good) {
                $goodsId = $good['goods_id'];
                if (!empty($ids) && in_array($good['goods_id'], $ids)) {
                    $g['is_choose'] = 1;
                } else {
                    $g['is_choose'] = 2;
                }
                $g['product_id'] = $goodsId;
                $g['product_name'] = $good['name'];
                $g['product_price'] = $good['price'];
                $tmpPicArr = $goodsImageService->getAllImageByPath($good['image'], 's');
                $g['product_image'] = $goodsImageService->getOneImage($tmpPicArr);
                $g['product_image'] = thumb_img($g['product_image'], 180, 180, 'fill');
                $detail = $foodshopGoodsLibraryService->getGoodsDetailByGoodsId($goodsId, $plat);
//                $g['has_format'] = $detail['has_format'];
//                $g['has_spec'] = $detail['has_spec'];
                $g['has_format'] = false;
                $g['has_spec'] = false;
                $g['stock_num'] = $detail['stock_num'];
                $g['sort'] = isset($detail['sort']) ? $detail['sort'] : 0;
                $g['unit'] = $detail['unit'];
                $g['json'] = isset($detail['json']) ? $detail['json'] : '';
//                $g['properties_list'] = $detail['properties_list'];
//                $g['properties_status_list'] = $detail['properties_status_list'];
//                $g['spec_list'] = $detail['spec_list'];
//                $g['list'] = $detail['list'];
                $g['properties_list'] = [];
                $g['properties_status_list'] = [];
                $g['spec_list'] = [];
                $g['list'] = $detail['list'];
                $g['today_sell_spec'] = isset($detail['today_sell_spec']) ? json_decode($detail['today_sell_spec'], true) : [];
                $goods[] = $g;
            }
            $package['goods'] = $goods;
            $packages[] = $package;
        }
        $returnArr = [];
        $returnArr['product_id'] = $package_info['id'];
        $returnArr['store_id'] = $package_info['store_id'];
        $returnArr['product_name'] = $package_info['name'];
        $product_image = [];
        if (empty($package_info['image'])) {
            $product_image[] = cfg('site_url') . '/tpl/Merchant/default/static/images/default_img.png';
        } else {
            $product_image[] = cfg('site_url') . $package_info['image'];
        }
        $returnArr['product_image'] = $product_image;
        $returnArr['des'] = $package_info['note'];
        $returnArr['product_price'] = $package_info['price'];
        $returnArr['subsidiary_piece'] = $packages;
        $returnArr['is_package_goods'] = true;
        return $returnArr;
    }

    /**根据条件获取数据
     * @param $param
     * @return mixed
     */
    public function getPackageDetailList($param)
    {
        $pid = $param['pid'] ?? 0;
        if (empty($pid)) {
            throw new \think\Exception(L_("参数有误"), 1003);
        }

        $where['pid'] = $pid;
        $order = [
            'id' => 'asc'
        ];
        $list = $this->getSome($where, true, $order);

        $packageDetailList = [];
        foreach ($list as $key => $value) {
            $arr['id'] = $value['id'];
            $arr['package_name'] = $value['package_name'];
            $arr['num'] = $value['num'];
            $packageDetailList[] = $arr;
        }

        $returnArr['list'] = $packageDetailList;
        return $returnArr;
    }

    /**根据条件获取数据
     * @param $param
     * @return mixed
     */
    public function getPackageDetailInfo($param)
    {
        $id = $param['id'] ?? 0;
        $where['id'] = $id;
        $order = [
            'id' => 'asc'
        ];
        $list = $this->getSome($where, true, $order);

        $packageDetailList = [];
        foreach ($list as $key => $value) {
            $arr['id'] = $value['id'];
            $arr['package_name'] = $value['package_name'];
            $arr['num'] = $value['num'];
            $packageDetailList[] = $arr;
        }

        $returnArr['list'] = $packageDetailList;
        return $returnArr;
    }

    /**根据条件获取套餐详情商品列表数据
     * @param $param
     * @return mixed
     */
    public function getPackageDetailGoodsList($param)
    {
        $id = $param['id'] ?? 0;

        if (empty($id)) {
            throw new \think\Exception(L_("参数有误"), 1003);
        }
        //详情
        $where = [
            'id' => $id
        ];

        $detail = $this->getOne($where, true);
        if (empty($detail['goods_detail'])) {
            $returnArr['list'] = [];
            $returnArr['choose'] = [];
            return $returnArr;
        }
        $goods_detail = json_decode($detail['goods_detail'], true);
        $goods_detail_choose = empty($detail['goods_detail_choose']) ? [] : json_decode($detail['goods_detail_choose'], true);
        $choose_goods = [];
        if (!empty($goods_detail_choose)) {
            foreach ($goods_detail_choose as $value) {
                $choose_goods[] = intval($value);
            }
        }

        $goods_ids = [];
        foreach ($goods_detail as $goods_id) {
            $goods_ids[] = $goods_id;
        }

        $where = ['goods_ids' => $goods_ids];
        $goods = (new FoodshopGoodsLibraryService())->getGoodsList($where)['list'];
        $list = [];
        foreach ($goods as $key => $g) {
            $arr['goods_id'] = $g['goods_id'];
            $arr['name'] = $g['name'];
            $arr['price'] = $g['price'];
            $arr['status'] = $g['status'];
            $arr['is_choose'] = 1;
            $arr['is_properties'] = $g['is_properties'];
            $list[] = $arr;

        }
        $returnArr['list'] = $list;
        $returnArr['choose'] = $choose_goods;

        return $returnArr;
    }

    /**保存或添加套餐详情
     * @param $param
     * @return bool
     * @throws \think\Exception
     */
    public function editPackageDetail($param)
    {
        $id = $param['id'] ?? 0;

        if (isset($param['package_name']) && empty($param['package_name'])) {
            throw new \think\Exception(L_("套餐分组名称必填"), 1003);
        }

        $item = (new FoodshopGoodsPackageService())->getOne($where = ['id' => $param['pid']]);
        if (empty($item)) {
            throw new \think\Exception("套餐不存在");
        }

        $data = [];

        if (isset($param['pid'])) {
            $data['pid'] = $param['pid'];
        }

        if (isset($param['num'])) {
            if ($param['num'] < 1) {
                throw new \think\Exception("分组设置可选数量不能为0");
            }
            $data['num'] = $param['num'];
        }

        $info = $this->getOne($where = ['id' => $param['id']], true);
        if (isset($param['goods_detail'])) {
            $num = $info['num'];
            if (count($param['goods_detail']) < $num) {
                throw new \think\Exception("当前分组可选数量是" . $num . ',请至少添加' . $num . '个菜品');
            }
            $data['goods_detail'] = json_encode($param['goods_detail']);
        }

        if (isset($param['package_name'])) {
            $data['package_name'] = $param['package_name'];
        }

        //分组可选数量修改后，重置可选菜品
        if($info && isset($data['num']) && $info['num'] != $data['num']){
            $data['goods_detail_choose'] = '';
        }
        
        if (isset($param['goods_detail_choose']) && !empty($param['goods_detail_choose'])) {
            $num = $info['num'];
            if (count($param['goods_detail_choose']) > $num) {
                throw new \think\Exception("当前分组可选数量是" . $num . ',必选菜最多设置' . $num . '个菜品');
            }
            $data['goods_detail_choose'] = json_encode($param['goods_detail_choose']);
        }

        if ($id > 0) {
            //修改
            $where = [
                'id' => $id
            ];

            $item = $this->getOne($where, true);
            if (empty($item)) {
                throw new \think\Exception("数据不存在");
            }

            $rs = $this->updateThis($where, $data);

        } else {
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
        return true;
    }


    /**删除套餐详情分组
     * @param $param
     * @return bool
     * @throws \think\Exception
     */
    public function delPackageDetail($param)
    {
        $where = [];
        if (isset($param['id'])) {
            $where = ['id' => $param['id']];
        }

        if (isset($param['pid'])) {
            $where = ['pid' => $param['pid']];
        }

        return $this->del($where);
    }

    /**根据id获取套餐详情信息
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

        $detail = $this->getOne($where, true);
        if (empty($detail)) {
            throw new \think\Exception("数据不存在");
        }

        $returnArr = [];
        $return['detail'] = $detail;
        return $return;
    }

    /**
     * 获得套餐选择商品列表
     * @param $where array 条件
     * @return array
     */
    public function getPackageGoodsList($param)
    {
        $storeId = $param['store_id'] ?? 0;
        $sortId = $param['sort_id'] ?? 0;
        $name = $param['keywords'] ?? 0;
        $id = $param['id'] ?? 0; // 套餐分组id

        $where = [
            'store_id' => $storeId,
            'sort_id' => $sortId,
            'name' => $name,
            'is_subsidiary_goods' => 0
        ];
        $goodsList = (new FoodshopGoodsLibraryService())->getGoodsList($where);

        $selectGoodsArr = [];
        $selectGoodsID = [];
        if ($id) {
            $where = [
                'id' => $id,
            ];
            $selectGoods = $this->getOne($where, true);
            $selectGoodsArr = empty($selectGoods['goods_detail']) ? [] : json_decode($selectGoods['goods_detail'], true);

            $selectAllGoods = $this->getSome($where = ['pid' => $selectGoods['pid']], true);

            foreach ($selectAllGoods as $all) {
                if (!empty($all['goods_detail'])) {
                    $goods_detail = json_decode($all['goods_detail'], true);
                    $selectGoodsID = array_merge($selectGoodsID, $goods_detail);
                }
            }
        }
        $returnArr = [];
        if ($goodsList['list']) {
            $sortList = (new FoodshopGoodsSortService())->getSortListByStoreId($storeId);
            $sortList = array_column($sortList, 'sort_name', 'sort_id');
            foreach ($goodsList['list'] as $goods) {
                $selected = 0;
                if (in_array($goods['goods_id'], $selectGoodsArr)) {
                    $selected = 1;
                }
                $returnArr['list'][] = [
                    'pigcms_id' => $goods['pigcms_id'],
                    'goods_id' => $goods['goods_id'],
                    'price' => $goods['min_price'] ?? $goods['price'],
                    'image' => $goods['product_image'],
                    'name' => $goods['name'],
                    'sort_name' => $sortList[$goods['spec_sort_id']],
                    'selected' => $selected,
                    'is_properties' => $goods['is_properties'],
                    'can_be_choose' => in_array($goods['goods_id'], $selectGoodsID) ? 0 : 1,
                ];
            }
        }
        return $returnArr;
    }

    /**
     * 根据条件一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $field)
    {
        if (empty($where)) {
            return [];
        }

        $package = $this->foodshopGoodsPackageDetailModel->getPackageDetail($where, $field);
        if (!$package) {
            return [];
        }

        return $package->toArray();
    }

    /**
     * 根据条件获取列表
     * @param $storeId
     * @return array
     */
    public function getSome($where = [], $field = true, $order = true, $page = 0, $limit = 0)
    {
        $res = $this->foodshopGoodsPackageDetailModel->getPackageDetailList($where, $field, $order, $page, $limit);

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
        $id = $this->foodshopGoodsPackageDetailModel->insertGetId($data);
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
        $rs = $this->foodshopGoodsPackageDetailModel->where($where)->update($data);

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
        return $this->foodshopGoodsPackageDetailModel->del($where);
    }

}