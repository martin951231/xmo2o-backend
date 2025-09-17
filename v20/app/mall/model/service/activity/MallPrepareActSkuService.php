<?php


namespace app\mall\model\service\activity;


use app\mall\model\db\MallPrepareActSku;


class MallPrepareActSkuService
{
    /**批量添加预售商品
     * @param $data
     * @return int
     */
    public function addAllPrepareActGoods($data)
    {
        return (new MallPrepareActSku())->addAll($data);
    }

    /**删除预售活动商品
     * @param $where
     * @return bool
     */
    public function delPrepareActGoods($where)
    {
        return (new MallPrepareActSku())->del($where);
    }

    /**获取预售商品信息
     * @param $act_id
     * @return array
     */
    public function getPrepareActSkuByActId($act_id)
    {
        return (new MallPrepareActSku())->getGoodsByActId($act_id);
    }
}