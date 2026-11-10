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
use app\community\model\db\HouseVillageUserBind;
use app\community\model\service\HouseWorkerService;
use app\command\BillExcelImport;
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
        $where[] = ['p.type', '=', 1];// 注意只支持一次性费用
        try {
            $list = $HouseNewChargeProjectService->getProjectList($where, 'p.id,p.type,p.name');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $list);
    }

    /**
     * 导入周期性账单前选择对应周期性项目.
     */
    public function getCycleChargeProject()
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
        $where[] = ['p.type', '=', 2];// 周期性账单导入仅支持周期性费用
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

    // 获取周期性账单导入模板
    public function getCycleImportTemplateUrl(){
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后再操作！');
        }
        try{
            $param=array(
                'village_id'=>$village_id,
                'opttype'=>ImportExcelService::UPLOAD_CYCLE_BILL_EXCEL,
                'subject_id'=>$this->request->param('subject_id', 0, 'intval'),
                'charge_project_id'=>$this->request->param('charge_project_id', 0, 'intval'),
            );
            $result = (new BillExcelService())->getCycleImportTemplateUrl($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$result);
    }

    public function getCyclePayImportTemplateUrl(){
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后再操作！');
        }
        $subject_id = $this->request->param('subject_id', 0, 'intval');
        $charge_project_id = $this->request->param('charge_project_id', 0, 'intval');
        if (empty($subject_id) || empty($charge_project_id)){
            return api_output(1001, [], '请选择收费科目和收费项目！');
        }
        try{
            $param=array(
                'village_id'=>$village_id,
                'opttype'=>ImportExcelService::UPLOAD_CYCLE_BILL_PAY_EXCEL,
                'subject_id'=>$subject_id,
                'charge_project_id'=>$charge_project_id,
            );
            $result = (new BillExcelService())->getCyclePayImportTemplateUrl($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$result);
    }

    // 获取车位管理费已缴账单导入模板
    public function getParkingPayImportTemplateUrl(){
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后再操作！');
        }
        try{
            $result = (new BillExcelService())->getParkingPayImportTemplateUrl([
                'village_id' => $village_id,
                'opttype' => ImportExcelService::UPLOAD_PARKING_BILL_PAY_EXCEL,
            ]);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$result);
    }

    public function getCyclePayUserList(){
        $village_id = $this->adminUser['village_id'];
        $room_id = $this->request->param('room_id', 0, 'intval');
        if (empty($village_id) || empty($room_id)){
            return api_output(0, []);
        }
        $where = [
            ['village_id', '=', $village_id],
            ['vacancy_id', '=', $room_id],
            ['status', '=', 1],
            ['type', 'in', [0, 1, 2, 3]],
        ];
        $list = (new HouseVillageUserBind())->getList($where, 'pigcms_id,uid,name,phone,type', 'type ASC,pigcms_id DESC');
        return api_output(0, $list ? $list->toArray() : []);
    }

    public function getCyclePayCashierList(){
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(0, []);
        }
        $whereArr = array(['village_id', '=', $village_id], ['is_del', '=', 0], ['xtype','=',2], ['relation_id','>',0], ['status', 'in', array(0, 1)]);
        $list = (new HouseWorkerService())->getAllWorkerList($whereArr, 'relation_id as opt_id,phone,name');
        return api_output(0, $list ? $list->toArray() : []);
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

    // 周期性账单上传文件解析
    public function cycleImportForExcel(){
        $village_id = $this->adminUser['village_id'];
        $excelPath = $this->request->param('inputFileName','','trim');
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后再操作！');
        }
        if (empty($excelPath)){
            return api_output(1001,[],'请上传文件');
        }
        try{
            $param=array(
                'village_id'=>$village_id,
                'inputFileName'=>app()->getRootPath().'..'.$excelPath,
            );
            $result = (new ImportExcelService())->readCycleBillImportFile($param);
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

    // 开始导入周期性账单
    public function startCycleImport()
    {
        $type      = ImportExcelService::UPLOAD_CYCLE_BILL_EXCEL;
        $subjectId = $this->request->param('subjectId', 0,'intval');
        $projectId = $this->request->param('charge_project_id', 0,'intval');
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后再操作！');
        }
        if (empty($subjectId) || empty($projectId)){
            return api_output(1001, [], '请选择收费项目！');
        }

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
                'type'                  => $type,
                'subjectId'             => $subjectId,
                'projectId'             => $projectId,
                'inputFileName'         => $inputFileName,
                'worksheetName'         => $worksheetName,
                'selectWorkSheetIndex'  => $selectWorkSheetIndex,
                'nowVillageInfo'        => $nowVillageInfo,
            );
            $result=(new BillExcelImport())->executeCycleBillExcel($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        $msg = isset($result['errorTotal']) && intval($result['errorTotal']) > 0 ? '导入完成，存在失败数据' : '导入成功';
        return api_output(0,$result,$msg);
    }

    public function startCyclePayImport()
    {
        $type      = ImportExcelService::UPLOAD_CYCLE_BILL_PAY_EXCEL;
        $subjectId = $this->request->param('subjectId', 0,'intval');
        $projectId = $this->request->param('charge_project_id', 0,'intval');
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后再操作！');
        }
        if (empty($subjectId) || empty($projectId)){
            return api_output(1001, [], '请选择收费项目！');
        }
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
                'type'                  => $type,
                'subjectId'             => $subjectId,
                'projectId'             => $projectId,
                'inputFileName'         => $inputFileName,
                'worksheetName'         => $worksheetName,
                'selectWorkSheetIndex'  => $selectWorkSheetIndex,
                'nowVillageInfo'        => $nowVillageInfo,
            );
            $result=(new BillExcelImport())->executeCycleBillPayExcel($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        $msg = isset($result['errorTotal']) && intval($result['errorTotal']) > 0 ? '导入完成，存在失败数据' : '导入成功';
        return api_output(0,$result,$msg);
    }

    // 开始导入车位管理费已缴账单
    public function startParkingPayImport()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后再操作！');
        }
        $inputFileName = $this->request->post('inputFileName','');
        $worksheetName = $this->request->post('worksheetName','');
        $selectWorkSheetIndex = $this->request->post('selectWorkSheetIndex',0);
        $nowVillageInfo = [
            'village_id' => $village_id,
            'village_name' => $this->adminUser['village_name'],
            'village_address' => $this->adminUser['village_address'],
            'account' => $this->adminUser['account'],
            'property_id' => $this->adminUser['property_id'],
            'property_phone' => $this->adminUser['property_phone'],
            'adminName' => $this->adminUser['adminName'],
            'user_name' => $this->adminUser['user_name'],
            'adminId' => $this->adminUser['adminId'],
            'loginType' => $this->adminUser['loginType'],
        ];
        try{
            $result = (new BillExcelImport())->executeCycleBillPayExcel([
                'type' => ImportExcelService::UPLOAD_PARKING_BILL_PAY_EXCEL,
                'subjectId' => 0,
                'projectId' => 0,
                'inputFileName' => $inputFileName,
                'worksheetName' => $worksheetName,
                'selectWorkSheetIndex' => $selectWorkSheetIndex,
                'nowVillageInfo' => $nowVillageInfo,
            ]);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        $msg = isset($result['errorTotal']) && intval($result['errorTotal']) > 0 ? '导入完成，存在失败数据' : '导入成功';
        return api_output(0,$result,$msg);
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
