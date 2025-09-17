<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      欠费账单导入
 */
namespace app\community\model\service\Excel;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class BillExcelService
{
    public function getImportTemplateUrl($param) {
        $opttype='billExcel';
        if(isset($param['opttype']) && $param['opttype']){
            $opttype= $param['opttype'];
        }
        $excelTableTile = [
            'A' => '楼栋名称', 'B' => '单元名称', 'C' => '楼层名称', 'D' => '房间号', 'E' => '欠缴费用名称', 'F' => '欠缴金额', 'G' => '备注','H'=>'账单计费开始时间','I'=>'账单计费结束时间','J'=>'账单生成时间(可不填)',
        ];
        $data = [
            [
                'A' => '1', 'B' => '1', 'C' => '5', 'D' => '2', 'E' => '21年物业费', 'F' => '5380.25', 'G' => '2021年欠缴的物业费','H'=>'2024-06-01','I'=>'2024-06-10','J'=>'',
            ],
            [
                'A' => '1', 'B' => '1', 'C' => '5', 'D' => '2', 'E' => '22年物业费', 'F' => '3526.5', 'G' => '2022年欠缴的物业费','H'=>'2024-06-20','I'=>'2024-06-21','J'=>'2024-06-01 10:30:00',
            ],
        ];
        $excelTemplate='欠费导入模板';
        if($opttype=='billPayExcel'){
            $excelTableTile = [
                'A' => '楼栋名称', 'B' => '单元名称', 'C' => '楼层名称', 'D' => '房间号', 'E' => '欠缴费用名称', 'F' => '已经金额', 'G' => '备注','H'=>'账单计费开始时间','I'=>'账单计费结束时间','J'=>'账单生成时间(可不填)','K'=>'缴费时间','L'=>'缴费方式（仅支持系统里的线下支付方式）',
            ];
            $data = [
                [
                    'A' => '1', 'B' => '1', 'C' => '5', 'D' => '2', 'E' => '21年物业费', 'F' => '5380.25', 'G' => '2021年已缴的物业费','H'=>'2024-06-01','I'=>'2024-06-10','J'=>'','K'=>'2024-06-01 14:30:00','L'=>'现金交易',
                ],
                [
                    'A' => '1', 'B' => '1', 'C' => '5', 'D' => '2', 'E' => '22年物业费', 'F' => '3526.5', 'G' => '2022年已缴的物业费','H'=>'2024-06-20','I'=>'2024-06-21','J'=>'2024-06-01 10:30:00','K'=>'2024-06-01 14:30:00','L'=>'微信',
                ],
            ];
            $excelTemplate='一次性已缴费用导入模板';
        }
        $result = $this->saveExcel($data, $excelTableTile, $opttype, $excelTemplate.'.xlsx', 'Xlsx');
        return $result;
    }

    public function saveExcel($data = [], $excelTableTile = [], $opttype='billExcel', $file_name = '', $inputFileType = 'Xlsx')
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        //设置单元格内容
        foreach ($excelTableTile as $pCoordinate => $excelHead) {
            $worksheet->setCellValue($pCoordinate.'1', $excelHead);
        }
        //设置单元格样式
        $worksheet->getStyle('A1:O1')->getFont()->setName('黑体')->setSize(12);
        foreach ($excelTableTile as $pColumn => $excelHead) {
            if($opttype=='billPayExcel' && $pColumn=='L'){
                $worksheet->getColumnDimension($pColumn)->setWidth(50);
            }else {
                $worksheet->getColumnDimension($pColumn)->setWidth(30);
            }
        }
        $len = count($data)-1;
        $row = 0;
        $i   = 0;
        $j   = 0;
        foreach ($data as $key => $val) {
            if ($i < $len+1) {
                $j = $i + 2; //从表格第2行开始
                foreach ($excelTableTile as $pCoordinate => $excelHead) {
                    $value=isset($val[$pCoordinate]) && $val[$pCoordinate] ? $val[$pCoordinate]:'';
                    $value=strval($value);
                    $worksheet->setCellValueExplicit($pCoordinate.$j, $value,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    //$worksheet->setCellValue($pCoordinate.$j, isset($val[$pCoordinate]) && $val[$pCoordinate] ? $val[$pCoordinate] : '');
                }
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
        $worksheet->getStyle('A1:O' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $fileInfo = $this->phpSpreadsheet($file_name, $spreadsheet, $inputFileType);
        if (!$fileInfo) {
            return [];
        }
        return $this->downloadExportFile($file_name);
    }

    public function phpSpreadsheet($filename,$spreadsheet, $inputFileType = 'Xlsx'){
        // 设置输出头部信息
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        try{
            $writer =  IOFactory::createWriter($spreadsheet, $inputFileType);
            $document_root = request()->server('DOCUMENT_ROOT');
            if (!$document_root) {
                $document_root = app()->getRootPath() . '..';
            }
            $basePath = $document_root.'/v20/runtime/';
            $filename = $basePath.$filename;
            $writer->save($filename);
            return true;
        }catch(\Exception $e){
            return false;
        }

    }

    /**
     * 下载表格
     */
    public function downloadExportFile($filename)
    {
        $document_root = request()->server('DOCUMENT_ROOT');
        if (!$document_root) {
            $document_root = app()->getRootPath() . '..';
        }
        $returnArr = [];
        if (!file_exists($document_root . '/v20/runtime/' . $filename)) {
            $returnArr['error'] = 1;
            return $returnArr;
        }

        $returnArr['url'] = cfg('site_url') . '/v20/runtime/' . $filename;
        $returnArr['error'] = 0;
        return $returnArr;
    }
}