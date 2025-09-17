<?php

/**
 * Author lkz
 * Date: 2023/1/10
 * Time: 11:54
 */

namespace app\community\controller\common;

use app\community\controller\CommunityBaseController;
use app\community\model\service\ImportExcelService;

class ImportExcelController extends CommunityBaseController
{
    //上传文件
    public function villageUploadFile(){
        $village_id = $this->adminUser['village_id'];
        $file = $this->request->file('file');
        $type = $this->request->param('type','','trim');
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        if (empty($file)){
            return api_output(1001,[],'请上传文件');
        }
        if (empty($type)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=array(
                'village_id'=>$village_id,
                'file'=>$file,
            );
            $result = (new ImportExcelService())->uploadFile($type,$param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$result);
    }
    
    //文件解析
    public function importForExcel(){
        $village_id = $this->adminUser['village_id'];
        $excelPath = $this->request->param('inputFileName','','trim');
        $type = $this->request->param('type','','trim');
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        if (empty($excelPath)){
            return api_output(1001,[],'请上传文件');
        }
        if (empty($type)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=array(
                'village_id'=>$village_id,
                'inputFileName'=>app()->getRootPath().'..'.$excelPath,
            );
            $result = (new ImportExcelService())->readImportFile($type,$param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$result);
    }

    //返回当前选择 sheet 和 系统支持的字段和 Excel 的列和表头
    public function startBindExcelCol(){
        $village_id = $this->adminUser['village_id'];
        $excelPath = $this->request->param('inputFileName','','trim');
        $type = $this->request->param('type','','trim');
        $worksheetName = $this->request->post('worksheetName','');
        $selectWorkSheetIndex= $this->request->post('selectWorkSheetIndex',0);
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        if (empty($excelPath)){
            return api_output(1001,[],'请上传文件');
        }
        if (empty($type)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=array(
                'village_id'=>$village_id,
                'inputFileName'=>app()->getRootPath().'..'.$excelPath,
                'worksheetName'=>$worksheetName,
                'selectWorkSheetIndex'=>$selectWorkSheetIndex
            );
            $result = (new ImportExcelService())->analysisImportFile($type,$param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$result);
        
    }

    //准备导入
    public function startImport(){
        $village_id = $this->adminUser['village_id'];
        $type = $this->request->param('type','','trim');
        $excelBinFieldRelationShip = $this->request->post('relationship','');
        $inputFileName          = $this->request->post('inputFileName','');
        $worksheetName          = $this->request->post('worksheetName','');
        $find_type             = $this->request->post('find_type','','trim');//数据重复时
        $find_value           = $this->request->post('find_value','','trim');//当{$find_value}重复时覆盖现有楼栋数据
        $selectWorkSheetIndex   = $this->request->post('selectWorkSheetIndex',0);
        $rule_id          = $this->request->post('rule_id',0,'intval');
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        if (empty($type)){
            return api_output(1001,[],'缺少必要参数');
        }
        $nowVillageInfo    = [
            'village_id'        => $this->adminUser['village_id'],
            'village_name'      => $this->adminUser['village_name'],
            'village_address'   => $this->adminUser['village_address'],
            'account'           => $this->adminUser['account'],
            'property_id'       => $this->adminUser['property_id'],
            'property_phone'    => $this->adminUser['property_phone'],
            'adminName'         => $this->adminUser['adminName'],
            'user_name'         => $this->adminUser['user_name'],
            'adminId'           => $this->adminUser['adminId'],
            'loginType'         => $this->adminUser['loginType'],
        ];
        try{
            $param=array(
                'excelBinFieldRelationShip' => $excelBinFieldRelationShip,
                'selectWorkSheetIndex'      => $selectWorkSheetIndex,
                'inputFileName'             => $inputFileName,
                'worksheetName'             => $worksheetName,
                'find_type'                 => $find_type,
                'find_value'                => $find_value,
                'nowVillageInfo'            => $nowVillageInfo,
                'rule_id'                   =>$rule_id,
            );
            $result = (new ImportExcelService())->triggerImportFile($type,$param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$result);
    }

    //获取当前导入的进度条
    public function refreshProcess(){
        $village_id = $this->adminUser['village_id'];
        $type = $this->request->param('type','','trim');
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        if (empty($type)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=array(
                'village_id'=>$village_id,
            );
            $result = (new ImportExcelService())->refreshProcess($type,$param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$result);
    }

    //查询导入记录
    public function getVillageImportRecord(){
        $village_id = $this->adminUser['village_id'];
        $type = $this->request->param('type','','trim');
        $yw_common_id = $this->request->param('yw_common_id','yw_common_id','trim');
        $page = $this->request->param('page',1,'int');
        $limit = $this->request->param('limit',10,'int');
        if (!$village_id){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        if (empty($type)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=array(
                'village_id'=>$village_id,
                'yw_common_id'=>$yw_common_id,
                'type'=>$type,
                'page'=>$page,
                'limit'=>$limit
            );
            $list = (new ImportExcelService())->getVillageImportRecord($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    
    //获取对应导入模板示例
    public function getImportTemplateUrl(){
        $village_id = $this->adminUser['village_id'];
        $type = $this->request->param('type','','trim');
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        if (empty($type)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=array(
                'village_id'=>$village_id,
            );
            $result = (new ImportExcelService())->getImportTemplateUrl($type,$param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$result);
    }

}