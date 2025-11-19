<?php


namespace service;

import("@.ORG.service.BaseService");

class ExcelService extends BaseService
{
    public function __construct()
    {
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
    }

    /**
     * author:Luo
     * 导出Excel
     * @param array $first_line_map
     * @param array $data
     * @param string $file_name
     * @param bool $is_save_local
     * @return string
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function exportExcel($first_line_map = [], $data = [], $file_name = '', $is_save_local = false)
    {
        // 创建一个新的 Excel 文件
        $objPHPExcel = new \PHPExcel();

        // 设置 Excel 文件的属性
        $excel_sheet = $objPHPExcel->setActiveSheetIndex(0);
        // 获取默认样式对象
        $style = $objPHPExcel->getDefaultStyle();
        // 设置对齐方式为居中
        $style->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT); // 水平左对齐
        $style->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER); // 垂直居中

        $excel_line = 1;
        foreach ($first_line_map as $_key => $_val) {
            $_col = chr(ord('A') + $_key);
            $excel_sheet->getColumnDimension($_col)->setWidth(20);
            $excel_sheet->setCellValue("{$_col}{$excel_line}", $_val[0]);
        }
        foreach ($data as $_key => $_val) {
            $excel_line++;
            foreach ($first_line_map as $_k => $_v) {
                $_col = chr(ord('A') + $_k);
                if ($_v[1] == 'auto_serial') {
                    $excel_sheet->setCellValue("{$_col}{$excel_line}", $excel_line - 1);
                } else {
                    $excel_sheet->setCellValue("{$_col}{$excel_line}", $_val[$_v[1]]);
                }
            }
        }

        if ($is_save_local) {
            $save_path = '';
            return $save_path;
        }

        // 设置响应头，告诉浏览器下载 Excel 文件
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $file_name . '-' . date("Ymdhis") . '.xls"');
        header('Cache-Control: max-age=0');

        // 将 Excel 文件输出到浏览器
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

}