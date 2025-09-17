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

namespace app\community\controller\common;

use app\common\model\service\UploadFileService;
use app\community\controller\CommunityBaseController;
use app\community\model\service\Excel\BillExcelService;
use app\community\model\service\HouseNewChargeProjectService;
use app\community\model\service\ImportExcelService;
use app\traits\CacheTypeTraits;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BillExcelController extends CommunityBaseController
{
    use CacheTypeTraits;
    
    /**
     * 导入前选择对应收费科目.
     */
    public function getChargeSubject()
    {
        $HouseNewChargeProjectService = new HouseNewChargeProjectService();
        try {
            $list = $HouseNewChargeProjectService->getSubject($this->adminUser['property_id'], ['park_new', 'non_motor_vehicle']);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);
    }

    /**
     * 导入欠费前选择对应项目(注意只支持一次性费用).
     */
    public function getChargeProject()
    {
        $village_id = $this->adminUser['village_id'];
        $subject_id = $this->request->post('subject_id');
        if (empty($subject_id)) {
            return api_output_error(1003, '请选择科目');
        }

        $HouseNewChargeProjectService = new HouseNewChargeProjectService();
        $where = [];
        $where[] = ['p.village_id', '=', $village_id];
        $where[] = ['p.subject_id', '=', $subject_id];
        $where[] = ['p.status', '=', 1];
        $where[] = ['p.type', '=', 1];// 仅支持一次性费用
        try {
            $list = $HouseNewChargeProjectService->getProjectList($where, 'p.id,p.type,p.name');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $list);
    }

    //获取对应导入模板示例
    public function getImportTemplateUrl(){
        $village_id = $this->adminUser['village_id'];
        $opttype = $this->request->param('opttype', 'billExcel', 'trim');
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        try{
            $param=array(
                'village_id'=>$village_id,
                'opttype'=>$opttype,
            );
            $result = (new BillExcelService())->getImportTemplateUrl($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$result);
    }
    
    public function billUploadFile()
    {
        $village_id = $this->adminUser['village_id'];
        $file = $this->request->file('file');
        $type = $this->request->param('type', 'billExcel', 'trim');
        $upload_dir = $this->request->param('upload_dir', 'billExcel', 'trim');
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        if (empty($file)) {
            return api_output(1001, [], '请上传文件');
        }
        if (empty($type)) {
            return api_output(1001, [], '缺少必要参数');
        }
        try {
            $param = array(
                'village_id' => $village_id,
                'file' => $file,
            );
            $result = (new ImportExcelService())->uploadFile($type, $param, $upload_dir);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    //上传文件解析
    public function importForExcel(){
        $village_id = $this->adminUser['village_id'];
        $excelPath = $this->request->param('inputFileName','','trim');
        $type = $this->request->param('type','billExcel','trim');
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
    
    //准备导入
    public function startImport()
    {
        $type      = $this->request->param('type','billExcel','trim');
        $subjectId = $this->request->param('subjectId', 0,'intval');
        $projectId = $this->request->param('projectId', 0,'intval');
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        if (empty($subjectId) || empty($projectId)){
            return api_output(1001, [], '请选择收费项目！');
        }

        //1、接收到 映射关系
        //2、放入队列
        //3、告知前端调整到进度展示画面
        //4、队列调用命令行执行

        $inputFileName          = $this->request->post('inputFileName','');
        $worksheetName          = $this->request->post('worksheetName','');
        $selectWorkSheetIndex   = $this->request->post('selectWorkSheetIndex',0);

        $nowVillageInfo    = [
            'village_id'        => $village_id,
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
                'selectWorkSheetIndex' => $selectWorkSheetIndex,
                'inputFileName'        => $inputFileName,
                'worksheetName'        => $worksheetName,
                'nowVillageInfo'       => $nowVillageInfo,
                'subjectId'            => $subjectId,
                'projectId'            => $projectId,
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
        $type = $this->request->param('type','billExcel','trim');
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
    public function getVillageImportRecord()
    {
        $village_id = $this->adminUser['village_id'];
        $type = $this->request->param('type', 'billExcel', 'trim');
        $yw_common_id = $this->request->param('yw_common_id','yw_common_id','trim');
        $page = $this->request->param('page', 1, 'int');
        $limit = $this->request->param('limit', 10, 'int');
        if (!$village_id) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        if (empty($type)) {
            return api_output(1001, [], '缺少必要参数');
        }
        try {
            $param = array(
                'village_id' => $village_id,
                'yw_common_id'=>$yw_common_id,
                'type' => $type,
                'page' => $page,
                'limit' => $limit
            );
            $list = (new ImportExcelService())->getVillageImportRecord($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);
    }
}