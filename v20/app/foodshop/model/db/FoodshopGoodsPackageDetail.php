<?php
/**
 * 店铺套餐详情表model
 * Created by subline.
 * Author: 钱大双
 * Date Time: 2020年12月14日17:17:14
 */


namespace app\foodshop\model\db;

use think\Model;

class FoodshopGoodsPackageDetail extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function del($where)
    {
        return $this->where($where)->delete();
    }

    public function getPackageDetail($where = [], $field = true)
    {
        $this->name = _view($this->name);
        $res = $this->getOne($where, $field);

        return $res;
    }

    public function getPackageDetailList($where = [], $field = true, $order = true, $page = 0, $limit = 0)
    {
        $this->name = _view($this->name);
        $res = $this->getProSome($where, $field, $order, $page, $limit);

        return $res;
    }
}