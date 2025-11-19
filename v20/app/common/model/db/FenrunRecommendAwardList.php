<?php
/**
 * 分润奖励金额 model
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/5/28 15:45
 */

namespace app\common\model\db;

use think\Model;

class FenrunRecommendAwardList extends Model
{
    /**
     * 添加分润奖励记录
     * User: chenxiang
     * Date: 2020/5/28 15:48
     * @param array $data
     * @return int|string
     */
    public function addRecommendAward($data = []) {
        $result = $this->insert($data);
        return $result;
    }
}