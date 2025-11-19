<?php
/**
 * 新版商城商品今日浏览量清空（每天24:00进行一次）
 */

namespace app\common\model\service\plan\file;

use app\mall\model\db\MallGoods;

class MallGoodsBrowseNumTodayService
{
    public function runTask()
    {
        //清空所有商品今日浏览量
        (new MallGoods())->where('is_del',0)->update(['browse_num_today'=>0]);
        return true;
    }
}