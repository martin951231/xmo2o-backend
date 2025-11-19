<?php
/**
 * 分润奖励金额操作
 * Created by.PhpStorm
 * User: chenxiang
 * Date: 2020/5/27 11:36
 */

namespace app\common\model\service;

use app\common\model\db\FenrunRecommendAwardList;

class FenrunRecommendAwardListService
{
    public $fenrunRecommendAwardListObj = null;
    public function __construct()
    {
        $this->fenrunRecommendAwardListObj = new FenrunRecommendAwardList();
    }

    /**
     * 添加分润奖励记录
     * User: chenxiang
     * Date: 2020/5/28 15:42
     * @param array $data
     * @return mixed
     */
    public function addRecommendAward($data = []) {
        $result = $this->fenrunRecommendAwardListObj->addRecommendAward($data);
        return $result;
    }
}
