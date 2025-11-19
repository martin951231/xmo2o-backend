<?php

class Tw_taskModel extends Model
{

    public function run()
    {
        $lockKey = "Tw_task_run";
        $lockRs = setLock($lockKey, 55);
        if ($lockRs['error_no']) {
            return true;
        }
        $tm = time();
        while (time() - $tm < 50) {
            $record = $this->where(['status' => 0])->order('id ASC')->find();
            if(empty($record)){
                break;
            }
            $data = json_decode($record['data'], true);
            try {
                switch ($record['type']) {
                    case 'sync_category':
                    case 'save_category':
                        $this->syncCategory($data);
                        break;
                    case 'sync_product':
                    case 'save_product':
                        $this->syncProduct($data);
                        break;
                    case 'sync_product_image':
                        $this->syncProductImage($data);
                        break;
                    case 'sync_order'://图维线下收银机订单同步至线上
                        $this->syncOrder($data);
                        break;
                    default:
                        break;
                }
                $rs = ['status' => 1, 'run_time' => $tm];
            } catch (Exception $e) {
                $rs = ['status' => 1, 'run_time' => $tm, 'run_result' => $e->getMessage()];
            }
            $this->where(['id' => $record['id']])->save($rs);
        }
        return true;
    }

    protected function getStoreIdByTwShopId($twShopId)
    {
        $store = M('Merchant_store')->where(['tw_shop_id' => $twShopId])->field('store_id')->find();
        if (empty($store)) {
            throw new Exception('店铺信息未查询到');
        }
        return $store['store_id'];
    }

    protected function syncCategory($data)
    {
        $shopId = $data['shop_id'] ?? '';
        if (empty($shopId)) {
            throw new Exception('商户编码参数有误');
        }
        $storeId = $this->getStoreIdByTwShopId($shopId);

        $twItemClsno = $data['item_clsno'];
        $twItemClsname = $data['item_clsname'];
        $twPidClsno = $data['parent_item_clsno'];
        $twSort = $data['item_sort'];
        $sortMod = M('Shop_goods_sort');

        //先判断是否存在上级分类，并获取上级分类id
        if ($twPidClsno) {
            $pidSort = $sortMod->where(['store_id' => $storeId, 'tw_item_clsno' => $twPidClsno])->find();
            if (empty($pidSort)) {
                throw new Exception('一级分类不存在');
            }
        }

        $thisSort = $sortMod->where(['store_id' => $storeId, 'tw_item_clsno' => $twItemClsno])->find();
        $updateData = [
            'tw_item_clsno' => $twItemClsno,
            'store_id' => $storeId,
            'sort_name' => $twItemClsname,
            'fid' => $pidSort['sort_id'] ?? 0,
            'operation_type' => $pidSort ? 0 : 1,
            'week' => '1,2,3,4,5,6,0',
            'sort' => $twSort,
            'level' => $pidSort ? 2 : 1,
            'status' => 1
        ];
        if ($thisSort) {
            //更新
            $rs = $sortMod->where(['store_id' => $storeId, 'tw_item_clsno' => $twItemClsno])->save($updateData);
        } else {
            //新增
            $rs = $sortMod->add($updateData);
        }
        if ($rs !== false) {
            return true;
        } else {
            throw new Exception($sortMod->getDbError());
        }
    }

    protected function syncProduct($data)
    {
        $shopId = $data['shop_id'] ?? '';
        if (empty($shopId)) {
            throw new Exception('商户编码参数有误');
        }
        $storeId = $this->getStoreIdByTwShopId($shopId);

        $sortMod = M('Shop_goods_sort');
        $goodsMod = M('Shop_goods');

        $clsno = $data['item_clsno'];
        $thisSort = $sortMod->where(['store_id' => $storeId, 'tw_item_clsno' => $clsno, 'level' => 2])->find();
        if (empty($thisSort)) {
            throw new Exception('商品类别不存在');
        }

        $updateData = [
            'name' => $data['item_name'],
            'sort_id' => $thisSort['sort_id'] ?? 0,
            'tw_item_no' => $data['item_no'],
            'number' => $data['item_subno'],
            'unit' => $data['item_unit'],
            'price' => $data['item_sale_price'],
            'cost_price' => $data['item_purchase_price'],
            'stock_num' => $data['stock_qty'] > 0 ? $data['stock_qty'] : 0,  //图维会存在超卖库存为负数
            'sort' => $data['item_sort'],
            'status' => $data['item_status'],
            'store_id' => $storeId,
            'des' => $data['item_detail_content']?implode('<br/>', $data['item_detail_content']):'',
            'last_time' => time()
        ];
        if (!empty($data['item_cover_image'])) {
            $updateData['image'] = $data['item_cover_image'];
        }
        if (!empty($data['item_detail_image'])) {
            $newDetailImage = explode(',',$data['item_detail_image']);
            foreach ($newDetailImage as $img) {
                $updateData['des'] .= "<br/><img src='" . $img . "'/>";
            }
        }

        $thisGood = $goodsMod->where(['store_id' => $storeId, 'tw_item_no' => $updateData['tw_item_no']])->find();

        if ($thisGood) {
            //更新,保留原来图片
            if (empty($data['item_detail_image'])) {
                $pos = stripos($thisGood['des'], '<img');
                if ($pos !== false) {
                    $updateData['des'] = $updateData['des'] . '<br/>' . substr($thisGood['des'], $pos);
                }
            }
            $rs = $goodsMod->where(['goods_id' => $thisGood['goods_id']])->save($updateData);
        } else {
            //新增
            $rs = $goodsMod->add($updateData);
        }
        if ($rs !== false) {
            return true;
        } else {
            throw new Exception($goodsMod->getDbError());
        }
    }

    protected function syncProductImage($data)
    {
        $shopId = $data['shop_id'] ?? '';
        if (empty($shopId)) {
            throw new Exception('商户编码参数有误');
        }
        $storeId = $this->getStoreIdByTwShopId($shopId);

        $goodsMod = M('Shop_goods');
        $thisGood = $goodsMod->where(['store_id' => $storeId, 'tw_item_no' => $data['item_no']])->find();
        if (empty($thisGood)) {
            throw new Exception('商品不存在');
        }

        $pos = stripos($thisGood['des'], '<img');
        $desc = ($pos === false) ? $thisGood['des'] : trim(substr($thisGood['des'], 0, $pos),'<br/>');
        if ($data['item_detail_image']) {
            foreach ($data['item_detail_image'] as $img) {
                if($desc){
                    $desc .= "<br/><img src='" . $img . "'/>";
                }else{
                    $desc .= "<img src='" . $img . "'/>";
                }
            }
        }
        $updateData = [
            'image' => $data['item_cover_image'] ? $data['item_cover_image'] : $thisGood['image'],
            'des' => $desc
        ];
        $goodsMod->where(['goods_id' => $thisGood['goods_id']])->save($updateData);
        return true;
    }

    public function delCategory($shopId, $clsNo)
    {
        if (empty($shopId)) {
            throw new Exception('商户编码参数有误');
        }
        $storeId = $this->getStoreIdByTwShopId($shopId);

        $sortMod = M('Shop_goods_sort');
        $goodsMod = M('Shop_goods');

        $thisSort = $sortMod->where(['store_id' => $storeId, 'tw_item_clsno' => $clsNo])->find();
        if (empty($thisSort)) {
            throw new Exception('商品类别不存在');
        }
        $sortIds = [];
        if ($thisSort['level']  != 2) {
            $sortIds = $sortMod->where(['fid' => $thisSort['sort_id']])->getField('sort_id', true);
        }
        if ($sortIds) {
            throw new Exception('当前分类下存在二级分类，请先删除二级分类');
        }

        $goodsExists = $goodsMod->where(['store_id' => $storeId, 'sort_id' => $thisSort['sort_id']])->find();
        if ($goodsExists) {
            throw new Exception('当前商品类别下存在商品，请先删除商品后再删除分类');
        }
        M('Shop_goods_sort')->where(['sort_id' => $thisSort['sort_id']])->delete();
        return true;
    }

    public function delProduct($shopId, $itemNo)
    {
        if (empty($shopId)) {
            throw new Exception('商户编码参数有误');
        }
        $storeId = $this->getStoreIdByTwShopId($shopId);
        M('Shop_goods')->where(['store_id' => $storeId, 'tw_item_no' => $itemNo])->delete();
        return true;
    }

    public function syncOrder($data)
    {
        $shopId = $data['shop_id'] ?? '';
        $twOrderid = $data['tw_order_no'] ?? '';
        if (empty($shopId) || empty($twOrderid)) {
            throw new Exception('shop_id/tw_order_no不能为空');
        }

        $orderMod = M('Tw_orders');
        $data['discount'] = !empty($data['discount']) ? json_encode($data['discount'], JSON_UNESCAPED_UNICODE) : '';
        $data['products'] = !empty($data['products']) ? json_encode($data['products'], JSON_UNESCAPED_UNICODE) : '';

        $find = $orderMod->where(['tw_order_no' => $twOrderid])->find();
        if ($find) {
            $rs = $orderMod->where(['id' => $find['id']])->save($data);
        } else {
            $rs = $orderMod->add($data);
        }

        if ($rs === false) {
            throw new Exception($orderMod->getDbError());
        }
        return true;
    }

    /**
     * 更新图维商品库存(定时按照店铺更新)
     * @param $storeId
     */
    public function updateTwGoodsStockByStoreId()
    {
        $lockKey = "Tw_update_stock_lock";
        $lockRs = setLock($lockKey, 55);
        if ($lockRs['error_no']) {
            return true;
        }
        if (!C('customization.open_tuwei_sync')) {
            return false;
        }

        $tm = time();
        import('@.ORG.Tw');

        $stores = M('Merchant_store')->where(['status' => 1, 'tw_shop_id' => ['neq', '']])->field('store_id,tw_shop_id')->select();
        foreach ($stores as $store) {
            $i = 1;
            while ($i <= 20) {
                $goods = D('Shop_goods')
                ->where(['tw_item_no' => ['neq', ''], 'store_id' => $store['store_id'], 'tw_stock_sync_time' => ['lt', $tm - 600]])
                    ->field('tw_item_no as item_no,goods_id')
                    ->limit(50)
                    ->select();

                if (empty($goods)) {
                    break;
                }

                $goodsids = array_column($goods, 'goods_id');
                D('Shop_goods')->where(['goods_id' => ['IN', $goodsids]])->save(['tw_stock_sync_time' => time()]);

                $params = [
                    'shop_id' => $store['tw_shop_id'],
                    'itemarry' => array_map(function ($r) {
                        unset($r['goods_id']);
                        return $r;
                    }, $goods)
                ];
                import('@.ORG.Tw');
                $result = (new Tw())->queryArrayStock($params);
                $stockinfo = $result['stockinfo'] ?? [];

                foreach ($stockinfo as $s) {
                    D('Shop_goods')->where(['tw_item_no' => $s['item_no']])->save(['stock_num' => $s['stock_qty'] > 0 ? $s['stock_qty'] : 0]);
                }
                $i++;
            }
        }
        unLock($lockKey);
        return true;
    }
}
