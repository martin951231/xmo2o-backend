<?php
/**
 * @author : liukezhu
 * @date : 2022/8/25
 */

namespace app\community\controller\manage_api\v1;
use app\community\controller\manage_api\BaseController;
use app\community\model\service\HousePublicRentalApplyService;
use app\community\model\service\HousePublicRentalRentingService;
use app\community\model\service\HouseVillageService;

class HousePublicRentalApiController  extends BaseController
{

    /**
     *
     *
    测试参数
    $village_id=50;
    $pigcms_id=4195;
    $this->request->log_uid=123457906;
    $record_id=33;
    $remarks='我要预约排号！！！！';
    $date='2022-09-07 14:46:18';

     $this->village_ids=[50];
    $this->_uid=452;
     *
     */

    protected $login_infos;
    protected $village_ids;

    public function initialize()
    {
        parent::initialize();
        $this->login_infos=$this->getInfo();
        if(isset($this->login_infos['error']) && !$this->login_infos['error']){
            $data=[
                'status'=>1001,
                'msg'=>$this->login_infos['msg'],
                'data'=>null,
                'refresh_ticket'=>''
            ];
            echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
        }
    }

    //集中处理获取village_id
    private function getInfo(){
        $info = $this->getLoginInfo();
        if (isset($info['status']) && $info['status']!=1000) {
            return ['error'=>false,'msg'=>$info['msg']];
        }
        $wid = $this->_uid;
        if (empty($wid)) {
            return ['error'=>false,'msg'=>'登录失败'];
        }
        $village_ids = (new HousePublicRentalApplyService())->getGrantVillageIds($info);
        if (empty($village_ids)) {
            return ['error'=>false,'msg'=>'当前账号暂无管理小区，请联系管理员'];
        }
        //todo 注意该$village_ids是数组！！！
        $this->village_ids=$village_ids;
        return ['error'=>true,'msg'=>'ok'];
    }

    /**
     * 办理入住== 获取审核数据
     * @author: liukezhu
     * @date : 2022/9/7
     * @return \json
     */
    public function getApplyRecordList(){
        $type = $this->request->post('type',0,'intval');
        $keyword = $this->request->post('keyword','','trim');
        $page = $this->request->param('page',1,'int');
        $limit = $this->request->param('limit',10,'int');
        if (empty($type)) {
            return api_output(1001,[],'缺少查询状态！');
        }
        try{
            $param=[
                'type'=>$type,
                'keyword'=>$keyword,
                'village_id'=>$this->village_ids,
                'uid'=>$this->_uid,
                'page'=>$page,
                'limit'=>$limit
            ];
            $data = (new HousePublicRentalApplyService())->getWorkerApplyRecordList($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data,'获取成功');
    }

    /**
     * 办理入住== 获取审核流程
     * @author: liukezhu
     * @date : 2022/8/25
     * @return \json
     */
    public function getUserApplyRecord(){
        $record_id = $this->request->post('record_id',0,'intval');
        if (empty($record_id)) {
            return api_output(1001,[],'缺少必填参数');
        }
        try{
            $param=[
                'record_id'=>$record_id,
                'village_id'=>$this->village_ids,
                'uid'=>$this->_uid,
                'is_manage'=>1
            ];
            $data = (new HousePublicRentalApplyService())->getWorkerExamineFlowData($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data,'获取成功');
    }

    /**
     * 办理入住== 提交审核
     * @author: liukezhu 
     * @date : 2022/9/8
     * @return \json
     */
    public function subOperationExamine(){
        $record_id = $this->request->param('record_id',0,'int');
        $single_id = $this->request->param('single_id',0,'int');
        $floor_id = $this->request->param('floor_id',0,'int');
        $layer_id= $this->request->param('layer_id',0,'int');
        $vacancy_id= $this->request->param('vacancy_id',0,'int');
        $examine_status= $this->request->param('examine_status',0,'int');
        $remarks= $this->request->param('remarks','','trim');
        $source_type = $this->request->post('source_type',1,'intval');
        if (empty($record_id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'village_id'=>$this->village_ids,
                'id'=>$record_id,
                'single_id'=>$single_id,
                'floor_id'=>$floor_id,
                'layer_id'=>$layer_id,
                'vacancy_id'=>$vacancy_id,
                'examine_status'=>$examine_status,
                'remarks'=>$remarks,
                'source'=>2,
                'source_type'=>$source_type,
                'log_operator'=>isset($this->login_infos['user']['login_name']) ? $this->login_infos['user']['login_name'] : ''
            ];
            $list = (new HousePublicRentalApplyService())->operationExamineRecord($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list,'操作成功');
    }

    /**
     * 办理入住== 查询办理记录
     * @author: liukezhu
     * @date : 2022/9/8
     * @return \json
     */
    public function getInspectionHouseRecord(){
        $page = $this->request->param('page',1,'int');
        $limit = $this->request->param('limit',10,'int');
        try{
            $param=[
                'village_id'=>$this->village_ids,
                'uid'=>$this->_uid,
                'page'=>$page,
                'limit'=>$limit
            ];
            $data = (new HousePublicRentalApplyService())->getWorkerInspectionRecordList($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data,'获取成功');
    }

    /**
     * 办理入住== 验房 详情
     * @author: liukezhu
     * @date : 2022/9/8
     * @return \json
     */
    public function getMyAppointRecordDetails(){
        $record_id = $this->request->param('record_id',0,'int');
        if (empty($record_id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'village_id'=>$this->village_ids,
                'record_id'=>$record_id
            ];
            $data = (new HousePublicRentalApplyService())->getMyAppointRecordDetails($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }


    /**
     * 办理退租== 记录列表
     * @author: liukezhu
     * @date : 2022/9/16
     * @return \json
     */
    public function getRentingRecordList(){
        $type = $this->request->post('type',0,'intval');
        $keyword = $this->request->post('keyword','','trim');
        $page = $this->request->param('page',1,'int');
        $limit = $this->request->param('limit',10,'int');
        if (empty($type)) {
            return api_output(1001,[],'缺少查询状态！');
        }
        try{
            $param=[
                'type'=>$type,
                'keyword'=>$keyword,
                'village_id'=>$this->village_ids,
                'uid'=>$this->_uid,
                'page'=>$page,
                'limit'=>$limit
            ];
            $data = (new HousePublicRentalRentingService())->getWorkerRentingRecordList($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data,'获取成功');
    }

    /**
     * 办理退租== 获取审核流程
     * @author: liukezhu
     * @date : 2022/9/16
     * @return \json
     */
    public function getUserRentingRecord(){
        $record_id = $this->request->post('record_id',0,'intval');
        if (empty($record_id)) {
            return api_output(1001,[],'缺少必填参数');
        }
        try{
            $param=[
                'record_id'=>$record_id,
                'village_id'=>$this->village_ids,
                'uid'=>$this->_uid,
                'source_type'=>2
            ];
            $data = (new HousePublicRentalApplyService())->getWorkerExamineFlowData($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data,'获取成功');
    }

    /**
     * 办理退租== 获取记录详情
     * @author: liukezhu
     * @date : 2022/9/16
     * @return \json
     */
    public function getMyRentingRecordDetails(){
        $record_id = $this->request->param('record_id',0,'int');
        if (empty($record_id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'village_id'=>$this->village_ids,
                'record_id'=>$record_id,
                'source_type'=>2,
                'is_edit'=>1,
                'cfrom'=>'manage'
            ];
            $data = (new HousePublicRentalApplyService())->getMyAppointRecordDetails($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     *办理退租== 记录列表
     * @author: liukezhu
     * @date : 2022/9/16
     * @return \json
     */
    public function getRentingHouseRecord(){
        $page = $this->request->param('page',1,'int');
        $limit = $this->request->param('limit',10,'int');
        try{
            $param=[
                'village_id'=>$this->village_ids,
                'uid'=>$this->_uid,
                'page'=>$page,
                'limit'=>$limit,
                'cfrom'=>'manage'
            ];
            $data = (new HousePublicRentalRentingService())->getWorkerRentingHouseRecord($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data,'获取成功');
    }


    /**
     * 办理退租== 验房操作
     * @author: liukezhu
     * @date : 2022/9/16
     * @return \json
     */
    public function operationInspection(){
        $record_id = $this->request->param('record_id',0,'int');
        $examine_status= $this->request->param('examine_status',0,'int');
        $remarks= $this->request->param('remarks','','trim');
        $imags= $this->request->param('imags');
        if($imags && !is_array($imags)){
            $imags=explode(';',$imags);
            $imags=!empty($imags)?$imags:array();
        }
        if (empty($record_id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'type'=>'is_inspection',
                'village_id'=>$this->village_ids,
                'record_id'=>$record_id,
                'examine_status'=>$examine_status,
                'remarks'=>$remarks,
                'source_type'=>2,
                'imags'=>$imags,
                'ht_log_operator'=>isset($this->login_infos['user']['login_name']) ? $this->login_infos['user']['login_name'] : ''
            ];
            $data = (new HousePublicRentalRentingService())->operationInspection($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data,'操作成功');
    }

}