<?php
/**
 * @author : liukezhu
 * @date : 2022/8/25
 */

namespace app\community\controller\village_api\User;
use app\community\controller\manage_api\BaseController;
use app\community\model\service\HousePublicRentalApplyService;
use app\community\model\service\HousePublicRentalRentingService;

class HousePublicRentalApiController extends BaseController
{
    /**
     *
     *
     测试参数
    $village_id=50;
    $pigcms_id=4195;
    $this->request->log_uid=123457906;
    $record_id=26;
    $remarks='我要预约排号！！！！';
    $date='2022-09-07 14:46:18';
    $examine_status=40;
    $remarks='通过了啊啊啊啊';
    $imags=[
    'http://o2o-static.pigcms.com/upload/house/000/000/050/62eb2a773db0a348.jpg',
    'http://o2o-static.pigcms.com/upload/house/000/000/050/62eb2a773db0a348.jpg',
    'http://o2o-static.pigcms.com/upload/house/000/000/050/62eb2a773db0a348.jpg'
    ];
     *
     */

    /**
     * 获取用户端入住申请
     * @author: liukezhu
     * @date : 2022/8/25
     * @return \json
     */
    public function getUserApplyRecordList(){
        $village_id = $this->request->post('village_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        $page = $this->request->param('page',1,'int');
        $limit = $this->request->param('limit',10,'int');
        if (empty($village_id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'village_id'=>$village_id,
                'pigcms_id'=>$pigcms_id,
                'uid'=>$this->request->log_uid,
                'page'=>$page,
                'limit'=>$limit
            ];
            $data = (new HousePublicRentalApplyService())->getUserApplyRecordList($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 获取排号申请
     * @author: liukezhu
     * @date : 2022/8/25
     * @return \json
     */
    public function getArrangingInfo(){
        $village_id = $this->request->post('village_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        $source_type = $this->request->post('source_type',1,'intval');
        if (empty($village_id)  || empty($source_type)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'village_id'=>$village_id,
                'pigcms_id'=>$pigcms_id,
                'uid'=>$this->request->log_uid,
                'source_type'=>$source_type,
            ];
            $data = (new HousePublicRentalApplyService())->getArrangingSet($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 获取预约排号
     * @author: liukezhu
     * @date : 2022/9/2
     * @return \json
     */
    public function getAppointArranging(){
        $village_id = $this->request->post('village_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        $source_type = $this->request->post('source_type',1,'intval');
        if (empty($village_id)  || empty($source_type)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'village_id'=>$village_id,
                'pigcms_id'=>$pigcms_id,
                'uid'=>$this->request->log_uid,
                'source_type'=>$source_type,
            ];
            $data = (new HousePublicRentalApplyService())->getArrangingSet($param,1);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 点击预约排号
     * @author: liukezhu
     * @date : 2022/9/2
     * @return \json
     */
    public function addArranging(){
        $village_id = $this->request->post('village_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        $date = $this->request->post('date','','trim');
        $remarks = $this->request->post('remarks','','trim');
        $source_type = $this->request->post('source_type',1,'intval');
        if (empty($village_id)  || empty($source_type)){
            return api_output(1001,[],'缺少必要参数');
        }
        if(empty($date)){
            return api_output(1001,[],'请选择预约时间');
        }
        try{
            $param=[
                'village_id'=>$village_id,
                'pigcms_id'=>$pigcms_id,
                'uid'=>$this->request->log_uid,
                'date'=>strtotime($date),
                'remarks'=>$remarks,
                'source_type'=>$source_type,
            ];
            $data = (new HousePublicRentalApplyService())->addArranging($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     *我的预约记录
     * @author: liukezhu
     * @date : 2022/9/5
     * @return \json
     */
    public function getMyAppointRecordList(){
        $village_id = $this->request->post('village_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        $page = $this->request->param('page',1,'int');
        $limit = $this->request->param('limit',10,'int');
        if (empty($village_id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'village_id'=>$village_id,
                'pigcms_id'=>$pigcms_id,
                'uid'=>$this->request->log_uid,
                'page'=>$page,
                'limit'=>$limit,
                'cfrom'=>'user'
            ];
            $data = (new HousePublicRentalApplyService())->getMyAppointRecordList($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 取消预约
     * @author: liukezhu
     * @date : 2022/9/7
     * @return \json
     */
    public function cancelMyAppointRecord(){
        $village_id = $this->request->post('village_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        $record_id = $this->request->param('record_id',0,'int');
        if (empty($village_id) || empty($record_id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'type'=>'cancel_appoint',
                'village_id'=>$village_id,
                'pigcms_id'=>$pigcms_id,
                'uid'=>$this->request->log_uid,
                'record_id'=>$record_id
            ];
            $data = (new HousePublicRentalApplyService())->operationMyAppointRecord($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 领取钥匙
     * @author: liukezhu
     * @date : 2022/9/7
     * @return \json
     */
    public function receiveKeyMyAppointRecord(){
        $village_id = $this->request->post('village_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        $record_id = $this->request->param('record_id',0,'int');
        if (empty($village_id)  || empty($record_id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'type'=>'receive_key',
                'village_id'=>$village_id,
                'pigcms_id'=>$pigcms_id,
                'uid'=>$this->request->log_uid,
                'record_id'=>$record_id
            ];
            $data = (new HousePublicRentalApplyService())->operationMyAppointRecord($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     *操作验房
     * @author: liukezhu
     * @date : 2022/9/8
     * @return \json
     */
    public function inspectionMyAppointRecord(){
        $village_id = $this->request->post('village_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        $record_id = $this->request->param('record_id',0,'int');
        $examine_status= $this->request->param('examine_status',0,'int');
        $remarks= $this->request->param('remarks','','trim');
        $imags= $this->request->param('imags');
        if($imags && !is_array($imags)){
            $imags=explode(';',$imags);
            $imags=!empty($imags)?$imags:array();
        }
        if (empty($village_id)  || empty($record_id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'type'=>'is_inspection',
                'village_id'=>$village_id,
                'pigcms_id'=>$pigcms_id,
                'uid'=>$this->request->log_uid,
                'record_id'=>$record_id,
                'examine_status'=>$examine_status,
                'remarks'=>$remarks,
                'imags'=>$imags,
            ];
            $data = (new HousePublicRentalApplyService())->operationMyAppointRecord($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 查看预约详情
     * @author: liukezhu
     * @date : 2022/9/7
     * @return \json
     */
    public function getMyAppointRecordDetails(){
        $village_id = $this->request->post('village_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        $record_id = $this->request->param('record_id',0,'int');
        if (empty($village_id)  || empty($record_id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'village_id'=>$village_id,
                'pigcms_id'=>$pigcms_id,
                'uid'=>$this->request->log_uid,
                'record_id'=>$record_id,
                'cfrom'=>'user'
            ];
            $data = (new HousePublicRentalApplyService())->getMyAppointRecordDetails($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }


    //====================================todo  办理退租 start======================================

    /**
     * 退租 申请列表
     * @author: liukezhu
     * @date : 2022/9/15
     * @return \json
     */
    public function getUserRentingRecordList(){
        $village_id = $this->request->post('village_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        $page = $this->request->param('page',1,'int');
        $limit = $this->request->param('limit',10,'int');
        if (empty($village_id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'village_id'=>$village_id,
                'pigcms_id'=>$pigcms_id,
                'uid'=>$this->request->log_uid,
                'page'=>$page,
                'limit'=>$limit
            ];
            $data = (new HousePublicRentalRentingService())->getUserApplyRecordList($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 退租 预约记录
     * @author: liukezhu
     * @date : 2022/9/15
     * @return \json
     */
    public function getMyRentingRecordList(){
        $village_id = $this->request->post('village_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        $page = $this->request->param('page',1,'int');
        $limit = $this->request->param('limit',10,'int');
        if (empty($village_id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'village_id'=>$village_id,
                'pigcms_id'=>$pigcms_id,
                'uid'=>$this->request->log_uid,
                'page'=>$page,
                'limit'=>$limit
            ];
            $data = (new HousePublicRentalRentingService())->getMyRentingRecordList($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 退租 取消预约
     * @author: liukezhu
     * @date : 2022/9/16
     * @return \json
     */
    public function cancelMyRentingRecord(){
        $village_id = $this->request->post('village_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        $record_id = $this->request->param('record_id',0,'int');
        if (empty($village_id) || empty($record_id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'type'=>'cancel_renting',
                'village_id'=>$village_id,
                'pigcms_id'=>$pigcms_id,
                'uid'=>$this->request->log_uid,
                'record_id'=>$record_id
            ];
            $data = (new HousePublicRentalRentingService())->operationInspection($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 退租 预约详情
     * @author: liukezhu
     * @date : 2022/9/16
     * @return \json
     */
    public function getMyRentingRecordDetails(){
        $village_id = $this->request->post('village_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        $record_id = $this->request->param('record_id',0,'int');
        if (empty($village_id)  || empty($record_id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'village_id'=>$village_id,
                'pigcms_id'=>$pigcms_id,
                'uid'=>$this->request->log_uid,
                'record_id'=>$record_id,
                'source_type'=>2
            ];
            $data = (new HousePublicRentalApplyService())->getMyAppointRecordDetails($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }


}