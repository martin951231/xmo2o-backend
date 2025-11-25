<?php
/**
 * Created by : PhpStorm
 * User: wangyi
 * Date: 2021/8/25
 * Time: 14:59
 */

namespace app\new_marketing\model\db;


use app\foodshop\model\db\MealStoreCategory;
use think\Model;

class NewMarketingClassRegion extends Model
{
	use \app\common\model\db\db_trait\CommonFunc;

	public function getClassesPriceList($where, $field, $order, $join, $type = 0,$pageSize=15)
	{
		$mealStoreCategory = new  MerchantCategory();//merchant_category
		$prefix            = config('database.connections.mysql.prefix');

		$arr = $mealStoreCategory->alias('msc')
			->where($where)
			->field($field)
			->order($order)
			->join($prefix . 'new_marketing_class_region' . ' nmcp', $join, 'LEFT');

		if ($type > 0) {
			$arr = $arr->select()->toArray();
		}else{
			$arr = $arr->paginate($pageSize);
		}
		return $arr;
	}

    //获取商家后台分类店铺数据
    public function getClassData($where) {
        $res = $this->where($where)->find();
        return $res;
    }

    //获取商家后台分类店铺列表
    public function getClassList($where) {
        $res = $this->where($where)->order('sort Desc')->select();
        if ($res) {
            $res = $res->toArray();
        }
        return $res;
    }

}