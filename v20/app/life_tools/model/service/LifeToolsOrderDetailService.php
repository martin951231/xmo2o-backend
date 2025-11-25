<?php
/**
 * 门票订单详情service
 * @date 2021-12-16 
 */

namespace app\life_tools\model\service;

use app\life_tools\model\db\LifeToolsOrderDetail;
use app\common\model\service\export\ExportService as BaseExportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\facade\Db;

class LifeToolsOrderDetailService
{
    public $lifeToolsOrderDetailModel = null;
    public function __construct()
    {
        $this->lifeToolsOrderDetailModel = new LifeToolsOrderDetail();
    }

    /**
     *批量插入数据
     * @param $data array
     * @return int|bool
     */
    public function addAll($data){
        if(empty($data)){
            return false;
        }

        $id = $this->lifeToolsOrderDetailModel->addAll($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     *获取多条条数据
     * @param $where array
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->lifeToolsOrderDetailModel->getSome($where,$field, $order, $page,$limit);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取核销列表
     * @param $param array
     * @param $type 1=导出
     * @return array
     */
    public function getVerifyList($param, $type = 0) {
        $limit = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        $type == 1 && $limit = 0;
        $where = [
            ['o.tools_id', '>', 0],
            ['d.status', '=', 2]
        ];
        if (!empty($param['mer_id'])) {
            $where[] = ['o.mer_id', '=', $param['mer_id']];
        }
        if (!empty($param['staffId'])) {
            $where[] = ['d.staff_id', '=', $param['staffId']];
        }
        if (!empty($param['search_by']) && !empty($param['keywords'])) {
            switch ($param['search_by']) {
                case 1:
                    $where[] = ['o.order_id|o.real_orderid|o.orderid', 'like', '%' . $param['keywords'] . '%'];
                    break;
                case 2:
                    $where[] = ['d.staff_name', 'like', '%' . $param['keywords'] . '%'];
                    break;
                case 3:
                    $where[] = ['o.uid', '=', $param['keywords']];
                    break;
                case 4:
                    $where[] = ['a.title', 'like', '%' . $param['keywords'] . '%'];
                    break;
            }
        }
        if (!empty($param['keyword'])) {
            $where[] = ['o.order_id|o.real_orderid|o.orderid|d.staff_name', 'like', '%' . $param['keyword'] . '%'];
        }
        if (empty($param['type'])) {
            $where[] = ['a.type', 'exp', Db::raw('= "course" or a.type = "stadium"')];
        } else {
            if ($param['type'] != 'all') {
                switch ($param['type']) {
                    case 1:
                        $type = 'stadium';
                        break;
                    case 2:
                        $type = 'course';
                        break;
                    case 3:
                        $type = 'scenic';
                        break;
                }
                $where[] = ['a.type', '=', $type];
            }
        }
        if (!empty($param['begin_time']) && !empty($param['end_time'])) {
            $where[] = ['d.last_time', '>=', strtotime($param['begin_time'])];
            $where[] = ['d.last_time', '<', strtotime($param['end_time']) + 86400];
        }
        $result = $this->lifeToolsOrderDetailModel->getVerifyList($where, $limit);
        if (!empty($result['data'])) {
            foreach ($result['data'] as $k => $v) {
                $result['data'][$k]['type']      = $v['type'] == 'stadium' ? '场馆' : ($v['type'] == 'course' ? '课程' : '景区');
                $result['data'][$k]['last_time'] = !empty($v['last_time']) ? date('Y-m-d H:i:s', $v['last_time']) : '无';
                $result['data'][$k]['info']      = $result['data'][$k]['type'] . '：' . $v['title'];
            }
        }
        return $result;
    }

    /**
     * 添加导出计划任务
     * @param $param
     * @param array $systemUser
     * @param array $merchantUser
     * @return array
     * @throws \think\Exception
     */
    public function exportVerifyRecord($param, $systemUser = [], $merchantUser = [])
    {
        $title = '体育健身核销记录';
        // $param['type'] = 'pc';
        $param['service_path'] = '\app\life_tools\model\service\LifeToolsOrderDetailService';
        $param['service_name'] = 'verifyRecordExportPhpSpreadsheet';
        $param['rand_number']  = time();
        $param['system_user']['area_id']  = $systemUser ? $systemUser['area_id'] : 0;
        $param['merchant_user']['mer_id'] = $merchantUser ? $merchantUser['mer_id'] : 0;
        // $result = (new BaseExportService())->addExport($title, $param, 'xlsx');
        // return $result;
        return $this->verifyRecordExportPhpSpreadsheet($param);
    }

    /**
     * 导出(Spreadsheet方法)
     * @param $param
     */
    public function verifyRecordExportPhpSpreadsheet($param)
    {
        $orderList   = $this->getVerifyList($param, 1)['data'];
        $spreadsheet = new Spreadsheet();
        $worksheet   = $spreadsheet->getActiveSheet();
        //设置单元格内容
        $worksheet->setCellValueByColumnAndRow(1, 1, '订单号');
        $worksheet->setCellValueByColumnAndRow(2, 1, '订单类型');
        $worksheet->setCellValueByColumnAndRow(3, 1, '店铺名称');
        $worksheet->setCellValueByColumnAndRow(4, 1, '店员名称');
        $worksheet->setCellValueByColumnAndRow(5, 1, '单价');
        $worksheet->setCellValueByColumnAndRow(6, 1, '核销时间');
        //设置单元格样式
        $worksheet->getStyle('A1:F1')->getFont()->setName('黑体')->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('A:F')->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EEEEEE');
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(13);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(42);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(30);
        $len = count($orderList);
        $j   = 0;
        $row = 0;
        $i   = 0;
        foreach ($orderList as $key => $val) {
            if ($i < $len) {
                $j = $i + 2; //从表格第2行开始
                $worksheet->setCellValueByColumnAndRow(1, $j, $orderList[$key]['real_orderid']);
                $worksheet->setCellValueByColumnAndRow(2, $j, $orderList[$key]['type']);
                $worksheet->setCellValueByColumnAndRow(3, $j, $orderList[$key]['store_name']);
                $worksheet->setCellValueByColumnAndRow(4, $j, $orderList[$key]['staff_name']);
                $worksheet->setCellValueByColumnAndRow(5, $j, '¥' . $orderList[$key]['price']);
                $worksheet->setCellValueByColumnAndRow(6, $j, $orderList[$key]['last_time']);
                $i++;
            }
            $row++;
        }
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $worksheet->getStyle('A1:F' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);
        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl];
    }

}