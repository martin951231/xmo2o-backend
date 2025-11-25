<?php

namespace app\building\model;

use think\Model;

//商场广告位
class ShoppingMallLikeSetting extends Model
{
    public function setGuessYouLike($params)
    {
        $mallId = $params['mall_id'] ?? 0;
        if (empty($mallId)) {
            throw new \Exception("请选择商场");
        }

        $update = [
            'total_num' => $params['total_num'] ?? 0,
            'mall_weight' => $params['mall_weight'] ?? 0,
            'mall_sort_rule' => $params['mall_sort_rule'] ?? 1,
            'mall_status' => $params['mall_status'] ?? 0,

            'shop_weight' => $params['shop_weight'] ?? 0,
            'shop_sort_rule' => $params['shop_sort_rule'] ?? 1,
            'shop_status' => $params['shop_status'] ?? 0,

            'group_weight' => $params['group_weight'] ?? 0,
            'group_sort_rule' => $params['group_sort_rule'] ?? 1,
            'group_status' => $params['group_status'] ?? 0,

            'dining_weight' => $params['dining_weight'] ?? 0,
            'dining_sort_rule' => $params['dining_sort_rule'] ?? 1,
            'dining_status' => $params['dining_status'] ?? 0
        ];

        $settting = $this->where('mall_id', $mallId)->find();
        if (empty($settting)) {
            $update['mall_id'] = $mallId;
            $this->insert($update);
        } else {
            $this->where('mall_id', $mallId)->update($update);
        }
        return true;
    }

    public function getGuessYouLike($mallId)
    {
        if (empty($mallId)) {
            return [];
        }
        return $this->where('mall_id', $mallId)->find();
    }
}
