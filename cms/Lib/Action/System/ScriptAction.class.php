<?php
/**
 * 同步脚本文件
 */

class ScriptAction extends BaseAction
{
    /**
     * 同步旧版限时优惠商品数据到3.0限时优惠活动
     * @author 张涛
     * @date 2020/05/08
     */
    public function syncTimeLimtedGoods()
    {
        set_time_limit(0);
        $limitMod = M('Time_limited_discount_goods');
        $limitSpecMod = M('Time_limited_discount_goods_spec');
        $goodMod = D('Shop_goods');
        $storeMod = M('Merchant_store_shop');
        $allWeek = '0,1,2,3,4,5,6';
        $tm = time();
        $field = 'store_id,goods_id,max_num,seckill_open_time,seckill_close_time,seckill_price,seckill_type,seckill_stock,spec_value,is_properties';
        $goods = $goodMod->field($field)
            ->where(['status' => 1, 'seckill_price' => ['GT', 0], 'seckill_open_time' => ['GT', 0], 'seckill_close_time' => ['GT', 0]])
            ->select();
        foreach ($goods as $g) {
            $formatSpec = $goodMod->format_spec_value($g['spec_value'], $g['goods_id'], $g['is_properties']);
            $formatSpecList = isset($formatSpec['list']) ? $formatSpec['list'] : [];

            //新版存在商品记录，则该商品不同步
            if ($limitMod->where(['goods_id' => $g['goods_id']])->find()) {
                continue;
            }
            $stockType = $storeMod->where(['store_id' => $g['store_id']])->getField('stock_type');
            $data = [
                'goods_id' => $g['goods_id'],
                'store_id' => $g['store_id']
            ];
            if (empty($g['spec_value'])) {
                $data['limit_price'] = $g['seckill_price'];
                $data['stock'] = $g['seckill_stock'];
                $data['origin_stock'] = $g['seckill_stock'];
            }
            if ($g['seckill_type'] == 0) {
                //固定时间段
                $data['start_date'] = date('Y-m-d', $g['seckill_open_time']);
                $data['end_date'] = date('Y-m-d', $g['seckill_close_time']);
                $data['week'] = $allWeek;
                $data['start_time'] = date('H:i:s', $g['seckill_open_time']);
                $data['end_time'] = date('H:i:s', $g['seckill_close_time']);
                $data['limit_num'] = $g['max_num'];
                $data['limit_type'] = 1;
                $data['stock_type'] = $stockType;
                $data['is_spec'] = $g['spec_value'] ? 1 : 0;
                $data['last_time'] = $tm;
                $data['create_time'] = $tm;
            } else if ($g['seckill_type'] == 1) {
                //每天的时间段
                $data['start_date'] = date('Y-m-d', $tm);
                $data['end_date'] = date('Y-m-d', strtotime('+1 year'));
                $data['week'] = $allWeek;
                $data['start_time'] = date('H:i:s', $g['seckill_open_time']);
                $data['end_time'] = date('H:i:s', $g['seckill_close_time']);
                $data['limit_num'] = $g['max_num'];
                $data['limit_type'] = 1;
                $data['stock_type'] = $stockType;
                $data['is_spec'] = $g['spec_value'] ? 1 : 0;
                $data['last_time'] = $tm;
                $data['create_time'] = $tm;
            }
            $limitId = $limitMod->add($data);
            if ($formatSpecList) {
                $specData = [];
                foreach ($formatSpecList as $k => $f) {
                    $specData[] = [
                        'limit_id' => $limitId,
                        'spec_index' => $k,
                        'stock' => $g['seckill_stock'],
                        'origin_stock' => $g['seckill_stock'],
                        'limit_price' => $f['seckill_price']
                    ];
                }
                $specData && $limitSpecMod->addAll($specData);
            }
        }
    }
}
