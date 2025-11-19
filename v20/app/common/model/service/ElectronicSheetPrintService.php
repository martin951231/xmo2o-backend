<?php
/**
 * 电子面单打印记录service
 * Create on 2020年11月27日14:21:45
 * Created by 钱大双
 */

namespace app\common\model\service;

use app\common\model\db\ElectronicSheetPrint;

class ElectronicSheetPrintService
{
    /**添加一条记录
     * @param $data
     * @return int|string
     */
    public function addPrintData($data)
    {
        return (new ElectronicSheetPrint())->addPrint($data);
    }

    /**更新一条记录
     * @param $data
     * @param $where
     * @return ElectronicSheetPrint
     */
    public function updatePrintData($data, $where)
    {
        return (new ElectronicSheetPrint())->updatePrint($data, $where);
    }

    /**获取信息
     * @param $where
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getInfo($where)
    {
        return (new ElectronicSheetPrint())->getOne($where);
    }
}