<?php


namespace app\mall\model\service\activity;


use app\mall\model\db\MallFullMinusDiscountLevel;

class MallFullMinusDiscountLevelService
{
	/**批量添加满减满折层级
     * @param $data
     * @return int
     */
    public function addAllDiscountLevel($data)
    {
        return (new MallFullMinusDiscountLevel())->addAll($data);
    }

    /**删除满减满折层级
     * @param $where
     * @return bool
     */
    public function delDiscountLevel($where)
    {
        return (new MallFullMinusDiscountLevel())->delOne($where);
    }
}