<?php
/**
 * Created by PhpStorm.
 * Author: lihongshun
 * Date Time: 2023/7/25 9:20
 */

namespace app\community\model\service;

use app\common\model\service\export\ExportService as BaseExportService;
use app\community\model\db\HouseVillageOrderTicketNumber;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseNewPayOrder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\facade\Db;

class HouseVillageOrderTicketNumberService
{

    public function getOneTicketNumber($whereArr = array())
    {
        $oneCarBindPosition = array();
        $houseVillageOrderTicketNumberDb = new HouseVillageOrderTicketNumber();
        if (!empty($whereArr)) {
            $oneCarBindPositionObj = $houseVillageOrderTicketNumberDb->getOneData($whereArr);
            if ($oneCarBindPositionObj && !$oneCarBindPositionObj->isEmpty()) {
                $oneCarBindPosition = $oneCarBindPositionObj->toArray();
            }
        }
        return $oneCarBindPosition;
    }

    public function addOneTicketNumber($addArr = array())
    {
        $houseVillageOrderTicketNumberDb = new HouseVillageOrderTicketNumber();
        $ret = $houseVillageOrderTicketNumberDb->addOneData($addArr);
        return $ret;
    }

    public function saveTicketNumber($whereArr = array(), $saveArr = array())
    {
        $houseVillageOrderTicketNumberDb = new HouseVillageOrderTicketNumber();
        $ret = $houseVillageOrderTicketNumberDb->saveData($whereArr, $saveArr);
        return $ret;
    }

    public function delTicketNumber($idd = 0, $property_id = 0)
    {
        $returnArr = array('err_code' => 0, 'msg' => '操作成功！');
        if ($idd < 1 || $property_id < 1) {
            $returnArr = array('err_code' => 1, 'msg' => '操作失败！');
            return $returnArr;
        }
        $houseVillageOrderTicketNumberDb = new HouseVillageOrderTicketNumber();
        $whereArr = array('id' => $idd, 'property_id' => $property_id);
        $saveArr = array('ticket_status' => 4, 'del_time' => time());
        $ret = $houseVillageOrderTicketNumberDb->updateAllData($whereArr, $saveArr);
        return $returnArr;
    }
    public function clearTicketNumberStatus($idd = 0, $property_id = 0){
        $returnArr = array('err_code' => 0, 'msg' => '操作成功！');
        if ($idd < 1 || $property_id < 1) {
            $returnArr = array('err_code' => 1, 'msg' => '操作失败！');
            return $returnArr;
        }
        $houseVillageOrderTicketNumberDb = new HouseVillageOrderTicketNumber();
        $whereArr = array('id' => $idd, 'property_id' => $property_id);
        $oneOrderTicketNumber=$houseVillageOrderTicketNumberDb->getOneData($whereArr,'*','id ASC');
        if($oneOrderTicketNumber && !$oneOrderTicketNumber->isEmpty() && $oneOrderTicketNumber['ticket_status']==1){
            $orderTicketNumberSave=array('summary_id'=>0,'ticket_status'=>0);
            $ret=$houseVillageOrderTicketNumberDb->saveData(array('id'=>$oneOrderTicketNumber['id']),$orderTicketNumberSave);
            $houseNewPayOrderDb= new HouseNewPayOrder();
            $whereTmpArr=array(['village_id','=',$oneOrderTicketNumber['village_id']]);
            $whereTmpArr[]=array('ticket_number','=',$oneOrderTicketNumber['ticket_fill_number']);
            if($oneOrderTicketNumber['order_ids']){
                $order_ids=json_decode($oneOrderTicketNumber['order_ids'],true);
                if($order_ids){
                    $whereTmpArr[]=array('order_id','in',$order_ids);
                }
            }
            $houseNewPayOrderDb->saveOne($whereTmpArr,array('ticket_number'=>''));
        }
        return $returnArr;
    }
    public function getCanUseTicketNumberList($where, $field = '*', $page = 0, $limit = 15, $order = 'id DESC')
    {
        $houseVillageOrderTicketNumberDb = new HouseVillageOrderTicketNumber();
        $listDataObj = $houseVillageOrderTicketNumberDb->getListData($where, $field, $order, $page, $limit);
        $listData=array();
        if ($listDataObj && !$listDataObj->isEmpty()) {
            $listData = $listDataObj->toArray();
        }

        return $listData;
    }
    public function getCanUseTicketNumberCount($where){
        $houseVillageOrderTicketNumberDb = new HouseVillageOrderTicketNumber();
        $xCount=$houseVillageOrderTicketNumberDb->getCount($where);
        $xCount=$xCount>0 ? $xCount:0;
        return $xCount;
    }
    public function updateOneOrderTicketNumber($whereArr,$saveData){
        $houseVillageOrderTicketNumberDb = new HouseVillageOrderTicketNumber();
        $oneOrderTicketNumber=$houseVillageOrderTicketNumberDb->getOneData($whereArr,'*','id ASC');
        $ret=false;
        if($oneOrderTicketNumber && !$oneOrderTicketNumber->isEmpty()){
            $ret=$houseVillageOrderTicketNumberDb->saveData(array('id'=>$oneOrderTicketNumber['id']),$saveData);
        }
        return  $ret;
    }
    public function getTicketNumberList($where, $field = '*', $page = 0, $limit = 15, $order = 'id DESC')
    {
        $returnArr = array('list' => array(), 'total' => 0, 'page_size' => $limit);
        $houseVillageOrderTicketNumberDb = new HouseVillageOrderTicketNumber();
        $total = $houseVillageOrderTicketNumberDb->getCount($where);
        $returnArr['total'] = $total ? $total : 0;
        $houseVillageDb = new HouseVillage();
        $village_name_arr = array();
        $listDataObj = $houseVillageOrderTicketNumberDb->getListData($where, $field, $order, $page, $limit);
        if ($listDataObj && !$listDataObj->isEmpty()) {
            $listData = $listDataObj->toArray();
            foreach ($listData as $kk => $vv) {
                $listData[$kk]['ticket_status_str'] = '未使用';
                if ($vv['ticket_status'] == 1) {
                    $listData[$kk]['ticket_status_str'] = '已占用';
                } else if ($vv['ticket_status'] == 2) {
                    $listData[$kk]['ticket_status_str'] = '已使用';
                } else if ($vv['ticket_status'] == 4) {
                    $listData[$kk]['ticket_status_str'] = '已删除';
                }
                $listData[$kk]['add_time_str'] = date('Y-m-d H:i:s', $vv['add_time']);
                $listData[$kk]['use_time_str'] = $vv['use_time'] > 100 ? date('Y-m-d H:i:s', $vv['use_time']) : '--';
                $listData[$kk]['update_time_str'] = $vv['update_time'] > 100 ? date('Y-m-d H:i:s', $vv['update_time']) : '--';
                $listData[$kk]['village_name'] = '';
                if (isset($village_name_arr[$vv['village_id']])) {
                    $listData[$kk]['village_name'] = $village_name_arr[$vv['village_id']]['village_name'];
                } else if ($vv['village_id'] > 0) {
                    $houseVillageObj = $houseVillageDb->getInfo(array('village_id' => $vv['village_id']), 'village_name');
                    if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
                        $houseVillage = $houseVillageObj->toArray();
                        $village_name_arr[$vv['village_id']] = $houseVillage;
                        $listData[$kk]['village_name'] = $houseVillage['village_name'];
                    }
                }
            }
        } else {
            $listData = array();
        }
        $returnArr['list'] = $listData;

        return $returnArr;
    }

    public function generateTicketNumber($property_id = 0, $generateArr = array())
    {
        fdump_api(['property_id' => $property_id, 'generateArr' => $generateArr], '000generateTicketNumber', 1);
        if ($property_id < 1 || empty($generateArr) || !isset($generateArr['start_num']) || !isset($generateArr['total_num']) || !isset($generateArr['village_id'])) {
            return true;
        }
        $max_num = $generateArr['start_num'] + $generateArr['total_num'];
        $max_num = intval($max_num);
        $max_num_str = strval($max_num);
        $max_num_len = strlen($max_num_str);
        $ticket_prefix = '';
        if (isset($generateArr['ticket_prefix'])) {
            $ticket_prefix = strval($generateArr['ticket_prefix']);
            $ticket_prefix=trim($ticket_prefix);
        }
        $nowtime = time();
        $houseVillageOrderTicketNumberDb = new HouseVillageOrderTicketNumber();
        $generate_count = 0;
        fdump_api(['property_id' => $property_id, 'max_num_len' => $max_num_len, 'nowtime' => $nowtime], '000generateTicketNumber', 1);
        for ($ii = 0; $ii < $generateArr['total_num']; $ii++) {
            $ticket_number = $generateArr['start_num'] + $ii;
            $ticket_number_str = strval($ticket_number);
            $ticket_number_str = str_pad($ticket_number_str, $max_num_len, '0', STR_PAD_LEFT);
            
            $ticket_number_str = $ticket_prefix . $ticket_number_str;
            
            $add_data = array('property_id' => $property_id, 'village_id' => $generateArr['village_id']);
            $add_data['ticket_prefix'] = $ticket_prefix;
            $add_data['ticket_number'] = $ticket_prefix . $ticket_number;
            $add_data['ticket_fill_number'] = $ticket_number_str;
            $add_data['generate_num'] = $generateArr['total_num'];
            $add_data['ticket_status'] = 0;
            $add_data['add_time'] = $nowtime;
            $houseVillageOrderTicketNumberDb->addOneData($add_data);
            $generate_count++;
            /*
            $whereArr=array(['property_id','=',$property_id],['village_id','=',$generateArr['village_id']]);
            $whereArr[]=array('ticket_fill_number','=',$ticket_number_str);
            $whereArr[]=array('del_time','<',10);
            $orderTicketNumber=$houseVillageOrderTicketNumberDb->getOneData($whereArr);
            if($orderTicketNumber && !$orderTicketNumber->isEmpty()){
                
            }else{
                $houseVillageOrderTicketNumberDb->addOneData($add_data);
            }
            */
        }
        fdump_api(['property_id' => $property_id, 'generate_count' => $generate_count, 'nowtime' => time()], '000generateTicketNumber', 1);
        return $generate_count;
    }

    public function handleImportExcel($file,$property_id=0)
    {
        $savepath = $file;
        $filed = [
            'A' => 'village_name',
            'B' => 'ticket_fill_number',
        ];
        $readerType='xls';
        $pathinfo = pathinfo($file);
        if($pathinfo && isset($pathinfo['extension']) && $pathinfo['extension']){
            $readerType=ucfirst($pathinfo['extension']);
        }
        $data = $this->readFile($_SERVER['DOCUMENT_ROOT'] . $savepath, $filed, $readerType);
        $data = array_values($data);
        $house_villageDb = new HouseVillage();
        $houseVillageOrderTicketNumberDb = new HouseVillageOrderTicketNumber();
        
        if (!empty($data)) {
            fdump_api(['property_id' => $property_id, 'file' => $file, 'data' => $data], '00handleImportExcelTicketNumber', 1);
            $nowtime=time();
            $data_print = [];
            $village_name_arr=array();
            foreach ($data as $value) {
                if (empty($value['village_name']) || empty($value['ticket_fill_number'])) {
                    continue;
                }elseif (strpos($value['village_name'],'小区名称')!==false || strpos($value['village_name'],'票据编号')!==false){
                    continue;
                }
               
                $village_name=strval($value['village_name']);
                $village_name=trim($village_name);
                $ticket_fill_number=strval($value['ticket_fill_number']);
                $ticket_fill_number=trim($ticket_fill_number);
                fdump_api(['value' => $value,'ticket_fill_number'=>$value['ticket_fill_number'],'ticket_fill_number22'=>$ticket_fill_number], '00handleImportExcelTicketNumber', 1);
                $village_id=0;
                if(isset($village_name_arr[$village_name]) && $village_name_arr[$village_name]){
                    $village_id=$village_name_arr[$village_name];
                }else{
                    $whereArr=array(['property_id','=',$property_id],['village_name','=',$village_name],['status','<',5]);
                    $village_info = $house_villageDb->getInfo($whereArr,'village_id');
                    if (empty($village_info) || $village_info->isEmpty()) {
                        $value['failReason'] = '小区不存在，请正确填写小区名称';
                        $data_print[] = $value;
                        continue;
                    }
                    $village_id=$village_info['village_id'];
                    $village_name_arr[$village_name]=$village_id;
                }
                $whereArr=array(['property_id','=',$property_id],['village_id','=',$village_id]);
                $whereArr[]=array('ticket_fill_number','=',$ticket_fill_number);
                $whereArr[]=array('del_time','<',10);
                $orderTicketNumber=$houseVillageOrderTicketNumberDb->getOneData($whereArr);
                if($orderTicketNumber && !$orderTicketNumber->isEmpty()){
                    $value['failReason'] = '此票据编号【'.$ticket_fill_number.'】已经存在了';
                    $data_print[] = $value;
                    continue;
                }
                $add_data = array('property_id' => $property_id, 'village_id' => $village_id);
                $add_data['ticket_prefix'] = '';
                $add_data['ticket_number'] = $ticket_fill_number;
                $add_data['ticket_fill_number'] = $ticket_fill_number;
                $add_data['generate_num'] = 1;
                $add_data['ticket_status'] = 0;
                $add_data['add_time'] = $nowtime;
                $houseVillageOrderTicketNumberDb->addOneData($add_data);
      
            }
            if (!empty($data_print)) {
                $title = ['小区名称（必填）', '票据编号(必填)', '失败原因'];
                $res = $this->exportExcel($title, $data_print, '导入票据编号' . date('YmdHis'));
                return ['error_code'=>1,'msg'=>'导入失败','fail_url'=>$res['url']];
            }
            return ['error_code'=>0,'msg'=>'导入成功','fail_url'=>''];
        }
        throw new \think\Exception("表格数据有误，请检查！");

    }
    /**
     * 读取文件
     * @author:zhubaodi
     * @date_time: 2021/5/21 13:21
     */
    public function readFile($file, $filed, $readerType)
    {
        $uploadfile = $file;
        $reader = IOFactory::createReader($readerType); //设置以Excel5格式(Excel97-2003工作簿)
        $PHPExcel = $reader->load($uploadfile); // 载入excel文件
        $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
        $highestRow = $sheet->getHighestDataRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数
        $data = [];
        for ($row = 2; $row <= $highestRow; $row++) //行号从1开始
        {
            $data[$row]=array();
            for ($column = 'A'; $column <= $highestColumm; $column++) //列数是以A列开始
            {
                if(isset($filed[$column])){
                    $data[$row][$filed[$column]] = $sheet->getCell($column . $row)->getValue();
                }
                
            }
        }
        return $data;
    }
    public function exportExcel($title, $data, $fileName)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(20);
        //  $sheet->getStyle('A')->getNumberFormat()->setFormatCode('@');
        //设置单元格内容
        $titCol = 'A';
        foreach ($title as $key => $value) {
            //单元格内容写入
            $sheet->setCellValue($titCol . '1', $value);
            $titCol++;
        }
        $row = 2;
        foreach ($data as $item) {
            $dataCol = 'A';
            foreach ($item as $value) {
                //单元格内容写入
                $sheet->setCellValue($dataCol . $row, $value);
                $dataCol++;
            }
            $row++;
        }
        //保存

        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $sheet->getStyle('A1:F' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $filename = $fileName . '.xls';
        $basePath=request()->server('DOCUMENT_ROOT').'/runtime/';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet,$basePath);
        return $this->downloadExportFile($filename);
    }

    public function downloadExportFile($param)
    {
        $returnArr = [];
        if (!file_exists(request()->server('DOCUMENT_ROOT') . '/runtime/' . $param)) {
            $returnArr['error'] = 1;
            return $returnArr;
        }
        $filename = $param;
        $ua = request()->server('HTTP_USER_AGENT');
        $ua = strtolower($ua);
        if (preg_match('/msie/', $ua) || preg_match('/edge/', $ua) || preg_match('/trident/', $ua)) { //判断是否为IE或Edge浏览器
            $filename = str_replace('+', '%20', urlencode($filename)); //使用urlencode对文件名进行重新编码
        }

        $returnArr['url'] = cfg('site_url') . '/runtime/' . $param;
        $returnArr['error'] = 0;

        return $returnArr;
    }
}
