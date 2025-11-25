<?php
/**
 * 标签打印机service
 * Created by subline.
 * Author: 钱大双
 * Date Time: 2021-2-24 16:18:21
 */

namespace app\merchant\model\service\print_order;

use app\merchant\model\db\LabelPrinter as LabelPrinterModel;
use app\foodshop\model\service\order_print\DiningPrintRulePrintService;

class LabelPrinterService
{
    public $labelPrinterModel = null;

    public function __construct()
    {
        $this->labelPrinterModel = new LabelPrinterModel();
    }

    /**
     * 根据条件获取数据
     * @param $where array
     * @return array
     */
    public function getPrintList($param)
    {
        $storeId = $param['store_id'] ?? 0;

        // 是否是获得打印机规则绑定的打印机列表
        $isBindRule = $param['is_bind_rule'] ?? 0;
        $id = $param['id'] ?? 0;

        $where = [];
        if ($storeId) {
            $where[] = ['store_id', '=', $storeId];
        }

        if ($isBindRule) {
            // 去掉已经绑定的打印机
            $wherePrint = [
                ['r.store_id', '=', $storeId],
                ['r.reciept_type', '=', 2]
            ];
            $id && $wherePrint[] = ['b.rule_id', '<>', $id];
            $bindPrintList = (new DiningPrintRulePrintService())->getBindPrintList($wherePrint, 'b.*');
            if ($bindPrintList) {
                $bindPrintId = array_column($bindPrintList, 'print_id');
                $where[] = ['pigcms_id', 'not in', implode(',', $bindPrintId)];
            }
        }
        // 查询列表
        $printList = $this->getSome($where);
        foreach ($printList as &$print) {
            // 打印机类型
            $print['print_type_txt'] = $this->getPrintType($print);
            // 纸张类型
            $print['paper_txt'] = $print['print_type'] ? L_('80mm') : L_('58mm');
            $print['key'] = $print['pigcms_id'];
        }

        $returnArr['list'] = $printList;
        return $returnArr;
    }

    /**
     * 根据条件获取打印机类型
     * @param $print array 打印机数据
     * @return string
     */
    public function getPrintType($print)
    {
        return L_("标签打印机");
    }


    /**获取一条数据
     * @param array $where 条件
     * @param array $order 排序
     * @return array|false
     */
    public function getOne($where, $order = [])
    {
        if (empty($where)) {
            return false;
        }

        $result = $this->labelPrinterModel->getOne($where, $order);
        if (empty($result)) {
            return [];
        }

        return $result->toArray();
    }

    /**获取多条数据
     * @param array $where 条件
     * @param bool $field 查询字段
     * @param bool $order 排序
     * @param int $page 页码
     * @param int $limit 每页条数
     * @return false
     */
    public function getSome($where, $field = true, $order = true, $page = 0, $limit = 0)
    {
        if (empty($where)) {
            return false;
        }

        try {
            $result = $this->labelPrinterModel->getSome($where, $field, $order, $page, $limit);
        } catch (\Exception $e) {
            return false;
        }

        return $result->toArray();
    }

    /**获取总数
     * @param array $where 条件
     * @return int
     */
    public function getCount($where)
    {
        if (empty($where)) {
            return 0;
        }

        try {
            $result = $this->labelPrinterModel->getCount($where);
        } catch (\Exception $e) {
            return 0;
        }

        return $result;
    }


    /**更新数据
     * @param array $where 条件
     * @param array $data 数据
     * @return bool
     */
    public function updateThis($where, array $data)
    {
        if (empty($data) || empty($where)) {
            return false;
        }

        try {
            $result = $this->labelPrinterModel->where($where)->update($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }


    /**批量插入数据
     * @param array $data 数据
     * @return false|int
     */
    public function addAll($data)
    {
        if (empty($data)) {
            return false;
        }

        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->labelPrinterModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }


    /**添加一条记录
     * @param array $data 数据
     * @return false|int|string
     */
    public function add($data)
    {
        $id = $this->labelPrinterModel->insertGetId($data);
        if (!$id) {
            return false;
        }

        return $id;
    }
}