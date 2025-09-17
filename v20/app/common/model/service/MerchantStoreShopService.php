<?php
/**
 * 商家 店铺快店配置
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/6/1 14:48
 */

namespace app\common\model\service;

use app\common\model\db\MerchantStoreShop;
use think\facade\Db;

class MerchantStoreShopService
{
    public $merchantStoreShopObj = null;

    public function __construct()
    {
        $this->merchantStoreShopObj = new MerchantStoreShop();
    }

    /**
     * 获取快店配置
     * User: chenxiang
     * Date: 2020/6/1 15:01
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getOne($where = [], $field = true)
    {
        $result = $this->merchantStoreShopObj->getOne($where, $field);
        return $result;
    }

    /**
     * @param $where
     * @param $data
     * 朱梦群
     * 更新店铺评分
     */
    public function updateOne($where, $data)
    {
        if(empty($where) || empty($data)){
            throw new \think\Exception('参数缺失');
        }
        $result = $this->merchantStoreShopObj->updateOne($where, $data);
        return $result;
    }

    //  同步外卖店铺评分
    public function syncShopScore()
    {
        $shopLists = Db::name('merchant_store_shop')->alias('shop')->join('merchant_store store', 'store.store_id = shop.store_id')
            ->where('store.status', '<>', 4)
            ->cursor();

        foreach ($shopLists as $shop) {
            $this->syncShopScoreByStoreId($shop['store_id']);
        }
        return true;
    }


    //  同步外卖店铺评分
    public function syncShopScoreByStoreId($storeId)
    {
        $shop = Db::name('merchant_store_shop')->where('store_id', $storeId)->find();
        if (empty($shop)) {
            return true;
        }
        $where = [
            ['s.status', '<>', 4],
            ['u.status', '=', 1],
            ['r.status', '<', 2],
            ['r.order_type', '=', 3],
            ['r.parent_id', '=', $storeId],
        ];

        cfg('open_shop_reply_check') && $where[] = ['r.check', '=', 2];

        $result = Db::name('reply')->alias('r')
            ->join('user u', 'r.uid = u.uid')
            ->join('merchant_store s', 'r.parent_id = s.store_id')
            ->where($where)
            ->field('count(r.pigcms_id) as count, sum(r.score) as total_score,sum(r.deliver_score) as total_deliver_score')
            ->find();

        if ($result && $result['count'] > 0) {
            //更新外卖店铺分数
            $update = [
                'reply_count' => $result['count'],
                'score_all' => $result['total_score'],
                'score_mean' => bcdiv($result['total_score'], $result['count'], 1),
                'reply_deliver_count' => $result['count'],
                'reply_deliver_score' => bcdiv($result['total_deliver_score'], $result['count'], 1),
            ];
            Db::name('merchant_store_shop')->where('store_id', $storeId)->update($update);
        } else {
            //离谱评分数据需要修复下,不能超过5分
            if ($shop['score_mean'] > 5 || $shop['reply_deliver_score'] > 5) {
                $update = [
                    'score_mean' => 5.0,
                    'reply_deliver_score' => 5.0,
                ];
                Db::name('merchant_store_shop')->where('store_id', $storeId)->update($update);
            }
        }
        return true;
    }
}