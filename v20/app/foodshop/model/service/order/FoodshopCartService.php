<?php

/**
 * 餐饮订单购物车service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/03 10:44
 */

namespace app\foodshop\model\service\order;

use app\foodshop\model\service\store\MerchantStoreFoodshopService;
use app\shop\model\service\goods\GoodsImageService as GoodsImageService;
use app\shop\model\service\goods\ShopGoodsService as ShopGoodsService;
use app\foodshop\model\service\goods\FoodshopGoodsLibraryService;
use app\foodshop\model\service\order\DiningOrderDetailService;
use app\foodshop\model\service\order\DiningOrderDetailTemporaryService;

class FoodshopCartService
{
    public function __construct()
    {
    }


    /**
     * 获取购物车详情
     * @param $param 
     * @param $user array 用户
     * @return array
     */
    public function getCartDetail($param, $user)
    {
        $orderId = isset($param['orderId']) ? $param['orderId'] : '0'; //订单id
        $tempId = isset($param['tempId']) ? $param['tempId'] : '0'; //临时订单id
        $storeId = 0;

        if (empty($orderId) && empty($tempId)) {
            throw new \think\Exception(L_('参数错误'), 1001);
        }
        $returnArr = [];
        $staffReturnArr = [];
        //商品数量
        $num = '0';
        // 购物车总价
        $totalPrice = '0';
        // 订单详情
        $nowOrder = [];
        // 商品列表
        $goods = [];
        if ($tempId) {
            // 临时订单详情
            $tempOrder = (new DiningOrderTemporaryService())->getOrderByTempId($tempId);
            if (empty($tempOrder)) {
                throw new \think\Exception(L_('订单不存在'), 1003);
            }

            if ($tempOrder['order_id']) {
                // 已绑定正式订单 查询正是订单购物车
                $orderId = $tempOrder['order_id'];
            }
        }

        // 桌台信息
        $returnArr['order'] = (object)[];

        $service = null;
        //套餐ID集合
        $package_id_arr = [];
        //套餐商品集合
        $packageCart = [];
        if ($orderId) { // 正式订单
            $nowOrder = (new DiningOrderService())->getWapOrderDetail($orderId);
            if (empty($nowOrder)) {
                throw new \think\Exception(L_('订单不存在'), 1003);
            }
            $returnArr['order'] = $nowOrder;
            $storeId = $nowOrder['store']['store_id'];

            // 商品列表
            $productCart = (new DiningOrderTempService())->getGoodsListByOrderId($orderId);
            // 多语言处理
            if (cfg('open_multilingual')) {
                $goods_ids = array_unique(array_column($productCart, 'goods_id'));
                $multilingual_goods_name = (new ShopGoodsService())->getGoodsListByCondition($where = [['goods_id', 'in', $goods_ids]]);
                $multilingual_goods_name = array_column($multilingual_goods_name, NULL, 'goods_id');

                foreach ($productCart as &$_product) {
                    $_product['name'] = isset($multilingual_goods_name[$_product['goods_id']]['name']) ? $multilingual_goods_name[$_product['goods_id']]['name'] : $_product
                    ['name'];
                }
            }
            foreach ($productCart as $key => $value)
            {
                if ($value['package_id'] > 0) {
                    $package_id_arr[$value['package_id']]['package_id'] = $value['package_id'];
                    $package_id_arr[$value['package_id']]['package_num'] = 0;
                    $packageCart[] = $value;
                    unset($productCart[$key]);
                }
            }
            $service = new DiningOrderTempService();
        } elseif ($tempId) { // 临时订单
            $nowOrder = (new DiningOrderTemporaryService())->getOrderByTempId($tempId);
            if (empty($nowOrder)) {
                throw new \think\Exception(L_('订单不存在'), 1003);
            }

            $returnArr['order'] = $nowOrder;
            $storeId = $nowOrder['store_id'];

            // 商品列表
            $productCart = (new DiningOrderDetailTemporaryService())->getGoodsListByTempId($tempId);
            // 多语言处理
            if (cfg('open_multilingual')) {
                $goods_ids = array_unique(array_column($productCart, 'goods_id'));
                $multilingual_goods_name = (new ShopGoodsService())->getGoodsListByCondition($where = [['goods_id', 'in', $goods_ids]]);
                $multilingual_goods_name = array_column($multilingual_goods_name, NULL, 'goods_id');

                foreach ($productCart as &$_product) {
                    $_product['name'] = isset($multilingual_goods_name[$_product['goods_id']]['name']) ? $multilingual_goods_name[$_product['goods_id']]['name'] : $_product
                    ['name'];
                }
            }
            foreach ($productCart as $key => $value)
            {
                if ($value['package_id'] > 0) {
                    $package_id_arr[$value['package_id']]['package_id'] = $value['package_id'];
                    $package_id_arr[$value['package_id']]['package_num'] = 0;
                    $packageCart[] = $value;
                    unset($productCart[$key]);
                }
            }
            $service = new DiningOrderDetailTemporaryService();
        }

        // 将相同的多个商品合并成一条记录
        $productCart = (new DiningOrderService())->combinationGoods($productCart);
        if (!empty($packageCart)) {
            $combinePackageProduct = (new ShopGoodsService())->combinationPackageData($packageCart, 'spec', 0);
        }

        // 计算商品总价
        foreach ($productCart as $_goods) {
            $goodSumMoney =  get_format_number($_goods['price'] * $_goods['num']);
            $totalPrice += $goodSumMoney;
        }

        $goods = [];
        if ($productCart) {
            // 组合附属商品
            $combineProduct = (new ShopGoodsService())->combinationData($productCart, 'spec', 0);

            // 获得商品信息
            $goodsId = array_column($combineProduct, 'goods_id');

            $where = [
                'goods_ids' => implode(',', $goodsId),
                'store_id' => $storeId,
            ];
            $goodsArr = (new FoodshopGoodsLibraryService())->getGoodsListByStoreId($storeId, $where, 'merchant');
            $image = array_column($goodsArr, 'image', 'goods_id');
            $minNum = array_column($goodsArr, 'min_num', 'goods_id');
            $onlStaff = array_column($goodsArr, 'only_staff', 'goods_id');
            $goodsImageService = new GoodsImageService();
            foreach ($combineProduct as $_goods) {
                if (!isset($image[$_goods['goods_id']])) {
                    $service->delGoodsById($_goods['id']);
                    continue;
                }
                $goodSumMoney =  get_format_number($_goods['price'] * $_goods['num']);
                $num += $_goods['num'];
                // 商品图片
                $productImage = '';
                $tmpPicArr = $goodsImageService->getAllImageByPath($image[$_goods['goods_id']], 's');
                $productImage = thumb_img($tmpPicArr[0], 180, 180, 'fill');
                $productImage = $productImage ?? '';


                $goods[] = [
                    'goods_id' => $_goods['goods_id'],
                    'sort_id' => $_goods['sort_id'],
                    'name' => $_goods['name'],
                    'spec' => trim(str_replace($_goods['spec_sub'], '', $_goods['spec'])),
                    'spec_arr' => trim(str_replace($_goods['spec_sub'], '', $_goods['spec'])) ? explode('、', trim(str_replace($_goods['spec_sub'], '', $_goods['spec']))) : [],
                    'spec_sub' => $_goods['spec_sub'],
                    'sub_list' => $_goods['sub_list'],
                    'price' => $_goods['price'],
                    'total_price' => $goodSumMoney,
                    'unit' => $_goods['unit'],
                    'mini_num' => isset($minNum[$_goods['goods_id']]) ? $minNum[$_goods['goods_id']] : '0',
                    'only_staff' => isset($onlStaff[$_goods['goods_id']]) ? $onlStaff[$_goods['goods_id']] : '0',
                    'product_image' => $productImage,
                    'num' => intval($_goods['num']),
                    'uniqueness_number' => $_goods['uniqueness_number']
                ];
            }
        }

        if (!empty($combinePackageProduct)) {
            $goods = array_merge($goods, $combinePackageProduct);
            foreach ($combinePackageProduct as $value) {
                $totalPrice += $value['price'] * $value['package_num'];
                $num += $value['package_num'];
                //统计订单中套餐数量
                $package_id_arr[$value['package_id']]['package_num'] += $value['package_num'];
            }
        }


        $staffReturnArr['goods_list'] = $returnArr['goods_list'] = $goods;
        $staffReturnArr['num'] = $returnArr['num'] = $num;
        $staffReturnArr['order_id'] = $returnArr['order_id'] = $orderId;
        $staffReturnArr['tempId'] = $returnArr['tempId'] = $tempId;
        $totalPrice = get_format_number($totalPrice);
        $staffReturnArr['total_price'] = $returnArr['total_price'] = ($totalPrice > 0 ? get_format_number($totalPrice) : 0);
        $staffReturnArr['go_pay_money'] = $returnArr['go_pay_money'] = ($totalPrice > 0 ? get_format_number($totalPrice) : 0);
        $staffReturnArr['package_detail_nums'] = empty($package_id_arr) ? [] : array_values($package_id_arr);

        //        var_dump($returnArr);
        $staffReturnArr['order'] = $returnArr['order'];
        $staffReturnArr['table_info'] = $returnArr['order']['table_info'] ?? [];
        if (!$user) {
            $returnArr = $staffReturnArr;
        }
        return $returnArr;
    }

    /**
     * 获取购物车详情
     * @param $param 
     * @param $user array 用户
     * @return array
     */
    public function addCart($param, $user = [], $staff = [])
    {
        $storeId = isset($param['storeId']) ? $param['storeId'] : '0'; //店铺id
        $orderId = isset($param['orderId']) ? $param['orderId'] : '0'; //订单id
        $tempId = isset($param['tempId']) ? $param['tempId'] : '0'; //临时订单id
        $product = isset($param['product']) ? $param['product'] : '0'; //添加的商品信息
        $operateType = isset($param['operate_type']) ? $param['operate_type'] : '0'; //0-加菜1-减菜
        $uniquenessNumber = isset($param['uniquenessNumber']) ? $param['uniquenessNumber'] : '0'; //商品的唯一标识（购物车里点加号或者减号的时候传值）
        $number = isset($param['number']) ? $param['number'] : '0'; //修改的商品数量（购物车里点加号或者减号的时候传值

        // 外卖店铺
        $shopStore = (new \app\shop\model\service\store\MerchantStoreShopService())->getStoreByStoreId($storeId);

        $orderType = 'order_id';
        $orderTypeId = $orderId;

        if ($tempId && empty($orderId)) {
            // 临时订单详情
            $tempOrder = (new DiningOrderTemporaryService())->getOrderByTempId($tempId);
            if (empty($tempOrder)) {
                throw new \think\Exception(L_('订单不存在'), 1003);
            }

            if ($tempOrder['order_id']) {
                // 已绑定正式订单 查询正是订单购物车
                $orderId = $tempOrder['order_id'];
                $orderTypeId = $orderId;
                $orderType = 'order_id';
            } else {
                $orderType = 'temp_id';
                $orderTypeId = $tempId;
            }
        }

        if ($orderId) { // 正式订单
            $nowOrder = (new DiningOrderService())->getOrderByOrderId($orderId);
            if (empty($nowOrder)) {
                throw new \think\Exception(L_('订单不存在'), 1003);
            }
        }

        $cartService = '';
        if ($orderId) { // 正式订单
            $cartService = new DiningOrderTempService();
        } else {
            $cartService = new DiningOrderDetailTemporaryService();
        }

        $saveGoods = []; //添加的主菜数组
        $saveSubGoods = []; //添加的附属菜数组
        $goodsList = [];
        $saveGoodsList = []; //订单商品购物车操作日志添加的数组

        if (empty($product) && empty($uniquenessNumber)) {
            throw new \think\Exception(L_('参数错误'), 1001);
        }

        if ($product) { // 添加
            $package_uniqueness_number = ''; //套餐商品唯一标识
            foreach ($product as $_goods) {
                $_goods['package_id'] = isset($_goods['package_id']) && !empty($_goods['package_id']) ? $_goods['package_id'] : 0;
                $goodsId = $_goods['productId'];
                $num = $_goods['count'];

                // 数量为0
                if ($num < 1) continue;

                $specIdArr = []; // 规格数组
                $str_spec = []; // 规格描述
                $str_properties = []; // 属性描述
                $properties = []; // 属性
                foreach ($_goods['productParam'] as $r) {
                    if ($r['type'] == 'spec' && $r['id']) { // 规格
                        $specIdArr[] = $r['id'];
                        $str_spec[] = $r['name'];
                    } else { // 属性
                        foreach ($r['data'] as $d) {
                            $str_properties[] = $d['name'];
                            $properties[] = $d['list_id'] . ':' . $d['id'];
                        }
                    }
                }

                // 规格id组合
                $specIdStr = $specIdArr ? implode('_', $specIdArr) : '';
                // 属性id组合
                $propertiesStr = $properties ? implode('_', $properties) : '';

                // 规格属性描述
                $str = '';
                $str_spec && $str = implode('、', $str_spec);
                $str_properties && $str = $str ? $str . '、' . implode('、', $str_properties) : implode('、', $str_properties);

                //校验商品信息和库存，获取商品实际的单价
                if ($_goods['package_id'] == 0) {
                    $t_return = (new FoodshopGoodsLibraryService())->checkStock($goodsId, $num, $specIdStr, $shopStore, $_goods['host_goods_id']);
                    if ($t_return['status'] != 1) { //验证不通过
                        return $t_return;
                    }
                } else {
                    $t_return = (new FoodshopGoodsLibraryService())->getPackageGoodsInfo($goodsId, $num, $specIdStr, $shopStore, $_goods['host_goods_id']);
                }

                if ($_goods['package_id'] == 0) {
                    $uniqueness_number = $_goods['uniqueness_number'];
                } else {
                    if ($orderId) {
                        $where = [
                            ['order_id', '=', $orderId],
                            ['package_id', '=', $_goods['package_id']],
                        ];
                    } else {
                        $where = [
                            ['temp_id', '=', $tempId],
                            ['package_id', '=', $_goods['package_id']],
                        ];
                    }
                    if (empty($package_uniqueness_number)) {
                        //套餐商品
                        if ($orderId) {
                            $cart_package_goods = (new DiningOrderTempService())->getGoodsListByCondition($where);
//                            $cart_package_goods = (new DiningOrderDetailService())->getOrderDetailByCondition($where);
                        } else {
                            $cart_package_goods = (new DiningOrderDetailTemporaryService())->getGoodsListByCondition($where);
                        }
                        if (!empty($cart_package_goods)) {
                            $cart_package_goods = array_unique(array_column($cart_package_goods, 'uniqueness_number'));
                            $count = count($cart_package_goods) + 1;
                            $uniqueness_number = $orderTypeId . '_' . $_goods['package_id'] . '_' . $count;
                            $package_uniqueness_number = $uniqueness_number;
                        } else {
                            $uniqueness_number = $orderTypeId . '_' . $_goods['package_id'] . '_1';
                            $package_uniqueness_number = $uniqueness_number;
                        }
                    }
                }
                if ($_goods['package_id'] > 0) {
                    $_goods['host_goods_id'] = 0; //套餐商品host_goods_id字段转为0
                }
                if (!$_goods['host_goods_id']) { //主菜
                    $saveGood = [
                        $orderType => $orderId ? $orderId : $tempId,
                        'store_id' => $storeId,
                        'goods_id' => $goodsId,
                        'sort_id' => $t_return['sort_id'],
                        'name' => $t_return['name'],
                        'price' => $t_return['price'],
                        'unit' => $t_return['unit'],
                        'num' => $num,
                        'spec' => $str,
                        'create_time' => time(),
                        'spec_id' => $specIdStr,
                        'properties' => $propertiesStr,
                        'uniqueness_number' => $_goods['package_id'] == 0 ? $uniqueness_number : $package_uniqueness_number,
                        'host_goods_id' => $_goods['host_goods_id'],
                        'staff_id' => $staff ? $staff['id'] : 0,
                        'is_staff' => $t_return['only_staff'] ?? 0,
                        'package_id' => $_goods['package_id'], //套餐ID
                        'package_num' => $_goods['package_id'] > 0 ? 1 : 0, //套餐数量，默认为1
                    ];
                    $saveGoods[] = $saveGood;
                    $saveGoodsList[] = $saveGood;
                } else { //附属菜
                    $saveSubGoods[] = [
                        $orderType => $orderId ? $orderId : $tempId,
                        'store_id' => $storeId,
                        'goods_id' => $goodsId,
                        'sort_id' => $t_return['sort_id'],
                        'name' => $t_return['name'],
                        'price' => $t_return['price'],
                        'unit' => $t_return['unit'],
                        'num' => $num,
                        'spec' => $str,
                        'create_time' => time(),
                        'spec_id' => $specIdStr,
                        'uniqueness_number' => $_goods['uniqueness_number'],
                        'host_goods_id' => $_goods['host_goods_id']
                    ];
                }
            }

            // 插入主菜数据
            if (!$saveGoods) {
                throw new \think\Exception(L_('没有需要加入购物车的商品'), 1003);
            }

            //            $checkGoods[] = $saveGoods;
            //            $result = $cartService->add($saveGoods);
            $checkGoods = $saveGoods;
            if ($saveSubGoods) { //存在附属菜，用add添加数据，附属商品host_id字段需要关联主菜商品添加数据返回的insertID
                if (!$orderId) {
                    unset($saveGood['staff_id']);
                    unset($saveGood['is_staff']);
                }
                $result = $cartService->add($saveGood);
            } else {
                if (!$orderId) {
                    foreach ($saveGoods as &$_value) {
                        unset($_value['staff_id']);
                        unset($_value['is_staff']);
                    }
                }
                $result = $cartService->addAll($saveGoods);
            }
            if (!$result) {
                throw new \think\Exception(L_('加入购物车失败，请重试'), 1003);
            }

            // 插入附属菜
            if ($saveSubGoods) {
                foreach ($saveSubGoods as &$_subGood) {
                    $_subGood['host_id'] = $result;
                }
                $result = $cartService->addAll($saveSubGoods);
                $checkGoods = array_merge($checkGoods, $saveSubGoods);
            }

            // 更新库存
            foreach ($checkGoods as $_goods) {
                (new FoodshopGoodsLibraryService())->updateStock($_goods);
            }

            // 添加日志
            if ($orderId) {
                if ($staff) { // 店员操作
                    $data = [
                        'operator_type' => 3,
                        'operator_name' => $staff['name'],
                        'operator_id' => $staff['id'],
                    ];
                    $logId = (new DiningOrderLogService())->addOrderLog($orderId, '11', '店员加入购物车', $user, $data);
                } else {
                    $logId = (new DiningOrderLogService())->addOrderLog($orderId, '12', '用户加入购物车', $user);
                }
            } else {
                if ($staff) { // 店员操作
                    $data = [
                        'operator_type' => 3,
                        'operator_name' => $staff['name'],
                        'operator_id' => $staff['id'],
                    ];
                    $logId = (new DiningOrderLogService())->addTempOrderLog($tempId, '11', '店员加入购物车', $user, $data);
                } else {
                    $logId = (new DiningOrderLogService())->addTempOrderLog($tempId, '12', '店员加入购物车', $user);
                }
            }

            foreach ($saveGoodsList as $save) {
                $data = [
                    $orderType => $orderId ? $orderId : $tempId,
                    'log_id' => $logId,
                    'goods_id' => $save['goods_id'],
                    'name' => $save['name'],
                    'operate_type' => 0,
                    'num' => $save['num'],
                    'package_id' => $save['package_id'],
                    'package_num' => $save['package_id'] == 0 ? 0 : 1,
                ];

                if ($user) {
                    $data['username'] = $user['nickname'];
                    $data['user_avatar'] = $user['avatar'];
                    $data['user_type'] = $user['user_type'];
                    $data['user_id'] = $user['user_id'];
                    $data['uid'] = $user['uid'];
                } elseif ($staff) {
                    $data['username'] = L_('店员');
                    $data['staff_id'] = $staff['id'];
                }
                (new DiningOrderGoodsLogService())->addGoodsLog($data);
            }

            return true;
        } elseif ($uniquenessNumber) { //购物车加减
            // 主菜
            $where = [];
            $where[] = ['uniqueness_number', '=', $uniquenessNumber];
            $where[] = ['host_goods_id', '=', '0'];
            $where[] = [$orderType, '=', $orderTypeId];
            $goodsList = $cartService->getGoodsListByCondition($where);
            if (empty($goodsList)) {
                throw new \think\Exception(L_('购物车不存在此商品'), 1003);
            }

            // 操作的商品列表
            foreach ($goodsList as $goods) {
                $checkGoods[] = [
                    'goods_id' => $goods['goods_id'],
                    'store_id' => $goods['store_id'],
                    'spec_id' => $goods['spec_id'],
                    'create_time' => time(),
                    'host_goods_id' => $goods['host_goods_id'],
                    'num' => $goods['package_id'] == 0 ? $number : $goods['num'] / $goods['package_num'] * $number,
                ];
            }

            if ($operateType == 1) { //减菜
                foreach ($goodsList as $_good) {
                    $delNum = $number; //减少数量
                    if ($_good['package_id'] == 0) {
                        $delNum = $delNum - $_good['num'];
                    } else {
                        $delNum = $delNum - $_good['package_num'];
                    }

                    if ($_good['package_id'] == 0) {
                        // 附属菜
                        $where = [];
                        $where[] = ['host_id', '=', $_good['id']];
                        $where[] = [$orderType, '=', $orderTypeId];
                        $subGoods = $cartService->getGoodsListByCondition($where);
                        if ($subGoods) {
                            foreach ($subGoods as $_subGoods) {
                                $checkGoods[] = [
                                    'goods_id' => $goodsList[0]['goods_id'],
                                    'store_id' => $goodsList[0]['store_id'],
                                    'spec_id' => $goodsList[0]['spec_id'],
                                    'create_time' => time(),
                                    'host_goods_id' => $goodsList[0]['host_goods_id'],
                                    'num' => $_subGoods['num'] / $_good['num'] * $delNum,
                                ];
                            }
                        }
                    }

                    if ($delNum > 0) { //数量不够，循环继续
                        // 删除数据
                        $res = $cartService->delGoodsById($_good['id']);
                        if ($_good['package_id'] == 0) {
                            // 删除附属菜
                            $where = [];
                            $where[] = ['host_id', '=', $_good['id']];
                            $where[] = [$orderType, '=', $orderTypeId];
                            $res = $cartService->delGoods($where);
                        }
                    } elseif ($delNum == 0) { //刚好够减，循环结束
                        if ($_good['package_id'] > 0) {
                            // 删除数据
                            $res = $cartService->delGoodsById($_good['id']);
                        } else {
                            // 删除数据
                            $res = $cartService->delGoodsById($_good['id']);
                            // 删除附属菜
                            $where = [];
                            $where[] = ['host_id', '=', $_good['id']];
                            $where[] = [$orderType, '=', $orderTypeId];
                            $res = $cartService->delGoods($where);
                            break;
                        }
                    } else { //够减，循环结束
                        // 更新数据
                        if ($_good['package_id'] == 0) {
                            $data = [
                                'num' => $_good['num'] - $number,
                            ];
                        } else {
                            //套餐数量
                            $package_num = $_good['package_num'] - $number;
                            //套餐商品数量
                            $num = $_good['num'] / $_good['package_num'] * $package_num;
                            $data = [
                                'package_num' => $package_num,
                                'num' => $num,
                            ];;
                        }
                        $cartService->updateById($_good['id'], $data);
                        if ($_good['package_id'] == 0) {
                            // 存在附属菜
                            if ($subGoods) {
                                foreach ($subGoods as $_subGoods) {
                                    $tmpSubGoods = [];
                                    $tmpSubGoods['num'] = $_subGoods['num'] / $_good['num'] * $data['num'];
                                    $cartService->updateById($_subGoods['id'], $tmpSubGoods);
                                }
                            }
                        }
                    }
                }
                // 更新库存
                foreach ($checkGoods as $_goods) {
                    (new FoodshopGoodsLibraryService())->updateStock($_goods, 1);
                }
            } else { //加菜
                foreach ($goodsList as $goods) {
                    // 更新数据
                    if ($goods['package_id'] == 0) {
                        $data = [
                            'num' => $goods['num'] + $number,
                        ];
                    } else {
                        //套餐数量
                        $package_num = $goods['package_num'] + $number;
                        //套餐商品数量
                        $num = $goods['num'] / $goods['package_num'] * $package_num;
                        $data = [
                            'package_num' => $package_num,
                            'num' => $num,
                        ];
                    }


                    if ($goods['package_id'] == 0) {
                        // 附属菜
                        $where = [];
                        $where[] = ['host_id', '=', $goods['id']];
                        $where[] = [$orderType, '=', $orderTypeId];
                        $subGoods = $cartService->getGoodsListByCondition($where);
                        // 存在附属菜
                        if ($subGoods) {
                            foreach ($subGoods as $k => $_subGoods) {
                                $checkGoods[] = [
                                    'goods_id' => $_subGoods['goods_id'],
                                    'store_id' => $_subGoods['store_id'],
                                    'num' => $_subGoods['num'] / $goods['num'] * $number,
                                    'spec_id' => $_subGoods['spec_id'],
                                    'create_time' => time(),
                                    'host_goods_id' => $_subGoods['host_goods_id'],
                                ];
                            }
                        }
                        foreach ($checkGoods as $_goods) {
                            //校验商品信息和库存，获取商品实际的单价
                            $t_return = (new FoodshopGoodsLibraryService())->checkStock($_goods['goods_id'], $_goods['num'], $_goods['spec_id'], $shopStore, $_goods['host_goods_id']);
                            if ($t_return['status'] != 1) { //验证不通过
                                return $t_return;
                            }
                        }
                    }
                    //验证商品库存end


                    //更新购物车数据
                    $cartService->updateById($goods['id'], $data);
                    if ($goods['package_id'] == 0) {
                        // 附属菜
                        $where = [];
                        $where[] = ['host_id', '=', $goods['id']];
                        $where[] = [$orderType, '=', $orderTypeId];
                        $subGoods = $cartService->getGoodsListByCondition($where);
                        // 存在附属菜
                        if ($subGoods) {
                            foreach ($subGoods as $_subGoods) {
                                $tmpSubGoods = [];
                                $tmpSubGoods['num'] = $_subGoods['num'] / $goods['num'] * $data['num'];
                                $cartService->updateById($_subGoods['id'], $tmpSubGoods);
                            }
                        }
                    }

                    // 更新库存
                    foreach ($checkGoods as $_goods) {
                        (new FoodshopGoodsLibraryService())->updateStock($_goods);
                    }
                }
            }
        }
        // 添加日志
        $data = [
            $orderType => $orderTypeId,
        ];

        if ($user) {
            $data['username'] = $user['nickname'];
            $data['user_avatar'] = $user['avatar'];
            $data['user_type'] = $user['user_type'];
            $data['user_id'] = $user['user_id'];
            $data['uid'] = $user['uid'];
        } elseif ($staff) {
            $data['username'] = L_('店员');
            $data['staff_id'] = $staff['id'];
        }
        if ($operateType == 1) {
            $msg = $staff ? '店员减菜' : '用户减菜';
        } else {
            $msg = $staff ? '店员加菜' : '用户加菜';
        }
        $logData = [];
        if ($staff) {
            $logData['operator_type'] = 3;
            $logData['operator_name'] = $staff['name'];
            $logData['operator_id'] = $staff['id'];
        }
        // 添加日志
        if ($orderId) {
            if ($staff) { // 店员操作
                $logId = (new DiningOrderLogService())->addOrderLog($orderId, '11', $msg, $user, $logData);
            } else {
                $logId = (new DiningOrderLogService())->addOrderLog($orderId, '12', $msg, $user);
            }
        } else {
            if ($staff) { // 店员操作
                $logId = (new DiningOrderLogService())->addTempOrderLog($tempId, '11', $msg, $user, $data);
            } else {
                $logId = (new DiningOrderLogService())->addTempOrderLog($tempId, '12', $msg, $user);
            }
        }
        foreach ($goodsList as $key => $goods) {
            $data['operate_type'] = $operateType == 1 ? 1 : 0;
            $data['goods_id'] = $goods['goods_id'];
            $data['name'] = $goods['name'];
            if ($goods['package_id'] == 0) {
                $data['num'] = $number;
                $data['package_num'] = 0;
            } else {
                $data['num'] = $goods['num'] / $goods['package_num'] * $number;
                $data['package_num'] = $number;
            }
            $data['package_id'] = $goods['package_id'];
            $data['log_id'] = $logId;
            (new DiningOrderGoodsLogService())->addGoodsLog($data);
        }
        return ['status' => 1];
    }

    /**
     * 获清空购物车
     * @param $param 
     * @param $user array 用户
     * @return array
     */
    public function clearCart($param, $user = [], $staff = [])
    {
        $orderId = isset($param['orderId']) ? $param['orderId'] : '0'; //订单id
        $tempId = isset($param['tempId']) ? $param['tempId'] : '0'; //临时订单id

        //购物车service
        $cartService = '';
        $delId = '';
        if ($tempId) {
            // 临时订单详情
            $tempOrder = (new DiningOrderTemporaryService())->getOrderByTempId($tempId);
            if (empty($tempOrder)) {
                throw new \think\Exception(L_('订单不存在'), 1003);
            }

            $orderType = 'temp_id';
            if ($tempOrder['order_id']) {
                // 已绑定正式订单 查询正是订单购物车
                $orderId = $tempOrder['order_id'];
                $orderType = 'temp_id';
                $delId = $orderId;
                $cartService = new DiningOrderTempService();
            } else {
                $delId = $tempId;
                $cartService = new DiningOrderDetailTemporaryService();
            }
        }

        if ($orderId) { // 正式订单
            $cartService = new DiningOrderTempService();
            $nowOrder = (new DiningOrderService())->getOrderByOrderId($orderId);
            $delId = $orderId;
            if (empty($nowOrder)) {
                throw new \think\Exception(L_('订单不存在'), 1003);
            }
        }

        // 购物车商品详情
        $goods = $cartService->getGoodsListByOrderId($delId);

        // 删除数据
        if (!$cartService->delGoodsByOrder($delId)) {
            throw new \think\Exception(L_('清空购物车失败，请重试'), 1003);
        }

        // 回滚库存
        foreach ($goods as $_goods) {
            $_goods['create_time'] = strtotime($_goods['create_time']);
            (new FoodshopGoodsLibraryService())->updateStock($_goods, 1);
        }

        // 添加日志
        $data = [];
        if ($staff) { // 店员操作
            $data = [
                'operator_type' => 3,
                'operator_name' => $staff['name'],
                'operator_id' => $staff['id'],
            ];
        }
        if ($orderId) {
            $logId = (new DiningOrderLogService())->addOrderLog($orderId, '14', '清空购物车', $user, $data);
        } else {
            $logId = (new DiningOrderLogService())->addTempOrderLog($tempId, '14', '清空购物车', $user, $data);
        }

        return true;
    }

    /**
     * 菜品选好了的接口
     * @param $param 
     * @param $user array 用户
     * @return array
     */
    public function confirmGoods($param, $user)
    {
        $orderId = isset($param['orderId']) ? $param['orderId'] : '0'; //订单id
        $tempId = isset($param['tempId']) ? $param['tempId'] : '0'; //临时订单id

        if (empty($orderId) && empty($tempId)) {
            throw new \think\Exception(L_('参数错误'), 1001);
        }

        $returnArr = [];
        //商品数量
        $num = '0';
        // 购物车总价
        $totalPrice = '0';

        if ($tempId) {
            // 临时订单详情
            $tempOrder = (new DiningOrderTemporaryService())->getOrderByTempId($tempId);

            $foodshopStore = (new \app\foodshop\model\service\store\MerchantStoreFoodshopService())->getStoreByStoreId($tempOrder['store_id']);


            if (empty($tempOrder)) {
                throw new \think\Exception(L_('订单不存在'), 1003);
            }

            if ($tempOrder['order_id']) {
                // 已绑定正式订单 查询正是订单购物车
                $orderId = $tempOrder['order_id'];
            } else {
                // 临时订单
                switch ($tempOrder['order_from']) {
                    case '3':
                        //在线预订选菜
                        // 生成正式订单 订单信息迁移 进入预定详情页
                        $orderId = (new DiningOrderTemporaryService())->moveOrder($tempId, '1');

                        // 添加日志
                        $logId = (new DiningOrderLogService())->addOrderLog($orderId, '1', '生成预订单', $user);

                        $returnArr['order_id'] = $orderId;
                        $returnArr['type'] = 'order_detail';
                        try {
                        } catch (\Exception $e) {
                            throw new \think\Exception($e->getMessage(), $e->getCode());
                        }

                        break;
                    case '4':
                        //直接选菜
                        try {
                            if ($foodshopStore['settle_accounts_type'] == 1) { //先吃后付进入提交菜品页
                                // 生成正式订单
                                $orderId = (new DiningOrderTemporaryService())->moveOrder($tempId, '21');

                                // 添加日志
                                $logId = (new DiningOrderLogService())->addOrderLog($orderId, '2', '直接选菜生成订单', $user);

                                $returnArr['order_id'] = $orderId;
                                $returnArr['type'] = 'confirm_goods';
                            } elseif ($foodshopStore['settle_accounts_type'] == 2) { //先付后付吃进入提交菜品页
                                $returnArr['temp_id'] = $tempId;
                                $returnArr['type'] = 'confirm_order';
                            }
                        } catch (\Exception $e) {
                            throw new \think\Exception($e->getMessage(), $e->getCode());
                        }
                        break;
                }
            }
        }

        if ($orderId) { // 正式订单
            $nowOrder = (new DiningOrderService())->getOrderByOrderId($orderId);
            if (empty($nowOrder)) {
                throw new \think\Exception(L_('订单不存在'), 1003);
            }

            $foodshopStore = (new \app\foodshop\model\service\store\MerchantStoreFoodshopService())->getStoreByStoreId($nowOrder['store_id']);
            $returnArr['order_id'] = $orderId;
            if (in_array($nowOrder['order_from'], ['0', '1', '2', '3'])) {
                //在线选桌 //在线选菜 //扫码
                if ($nowOrder['status'] <= 1 || $nowOrder['status'] == 4) { //预订单
                    if ($param['scan'] == 1 && !$param['table_id']) {
                        // 扫通用码，须自动落座

                        // 更新数据
                        $saveData = [
                            'status' => '21',
                        ];

                        // 保存订单信息
                        try {
                            $result = (new DiningOrderService())->updateByOrderId($orderId, $saveData);

                            // 添加日志
                            (new DiningOrderLogService())->addOrderLog($orderId, '15', '签到落座', $user);
                            $returnArr['type'] = 'confirm_goods';
                        } catch (\Exception $e) {
                            throw new \think\Exception(L_("落座失败，请稍后重试"), 1003);
                        }
                    } else {
                        $returnArr['type'] = 'order_detail';
                    }
                } elseif ($nowOrder['status'] < 40) { //就餐中
                    $returnArr['type'] = 'confirm_goods';
                } else { //已完成 已取消
                    $returnArr['type'] = 'order_detail';
                }
            } else {
                //                if($foodshopStore['settle_accounts_type']==1){//先吃后付进入提交菜品页
                //                    $returnArr['type'] = 'confirm_goods';
                //                }elseif($foodshopStore['settle_accounts_type']==2){//先付后吃进入提交页
                //                    $returnArr['type'] = 'confirm_order';
                //                }
                // 正式订单说明已经提交过一次支付
                $returnArr['type'] = 'confirm_goods';
            }
        }
        return $returnArr;
    }

    /**
     * 保存购物车
     * @param $orderId 
     * @param $user array 用户
     * @return array
     */
    public function saveCart($param, $user, $staff = [])
    {
        $orderId = $param['order_id'];
        if (empty($orderId)) {
            throw new \think\Exception(L_('参数错误'), 1001);
        }

        // 获取订单信息
        $nowOrder = (new DiningOrderService())->getOrderByOrderId($orderId);
        if (empty($nowOrder)) {
            throw new \think\Exception(L_('订单不存在'), 1003);
        }

        // 不在就餐中
        if ($nowOrder['status'] < 20 || $nowOrder['status'] >= 40) {
            throw new \think\Exception(L_('该订单无法确认菜品'), 1003);
        }

        // 店铺信息
        $foodshopStore = (new MerchantStoreFoodshopService())->getStoreByStoreId($nowOrder['store_id']);

        $status = 0;
        if ($nowOrder['settle_accounts_type'] == 1) { //先吃后付
            $status = 1;
        }

        // 提交购物车
        $res = (new DiningOrderTempService())->saveCart($param, $status, $staff);
        $goodsList = $res['goods_list'] ?? [];
        $orderNum = $res['order_num'] ?? 0;
        if ($nowOrder['status'] == 21) { //修改点餐中状态
            $data['status'] = 20;
            (new DiningOrderService())->updateByOrderId($orderId, $data);
        }

        if ($nowOrder['settle_accounts_type'] == 1) { //先吃后付
            if ($staff) {
                $nowOrder['staff_name'] = $staff['name'];
            }
            //通知店员打印菜品
            (new DiningOrderService())->callStaff($nowOrder, $goodsList, 0, $orderNum);
        }


        return true;
    }
}
