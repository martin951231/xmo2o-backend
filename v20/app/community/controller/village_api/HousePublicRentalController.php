<?php
/**
 * todo 公租房
 * @author : liukezhu
 * @date : 2022/8/3
 */
namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HousePublicRentalApplyService;
use app\community\model\service\HousePublicRentalRentingService;
use think\App;

class HousePublicRentalController extends CommunityBaseController{


    protected $menus;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->menus=(isset($this->adminUser['menus']) && !empty($this->adminUser['menus'])) ? (is_array($this->adminUser['menus']) ? $this->adminUser['menus'] : explode(',',$this->adminUser['menus']))  : [];
    }

    //====================================todo 办理入住 start======================================

    /**
     * 入驻申请列表
     * @author: liukezhu
     * @date : 2022/8/3
     * @return \json
     */
    public function getApplyList(){
        $key= $this->request->param('key','','trim');
        $value = $this->request->param('value','','trim');
        $date= $this->request->param('date','','trim');
        $flow_status = $this->request->param('flow_status',0,'int');
        $page = $this->request->param('page',0,'int');
        $limit = $this->request->param('limit',10,'int');
        try{
            $param=[
                'type'=>1,
                'village_id'=>$this->adminUser['village_id'],
                'key'=>$key,
                'value'=>$value,
                'date'=>$date,
                'flow_status'=>$flow_status,
                'page'=>$page,
                'limit'=>$limit,
                'menus'=>$this->menus
            ];
            $list = (new HousePublicRentalApplyService())->getApplyList($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     *删除申请
     * @author: liukezhu
     * @date : 2022/8/5
     * @return \json
     */
    public function applyDel(){
        $id = $this->request->param('id',0,'int');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $list = (new HousePublicRentalApplyService())->applyDel($this->adminUser['village_id'],$id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$list){
            return api_output_error(1001,'数据不存在');
        }else{
            return api_output(0,$list);
        }
    }

    /**
     * 获取附件列表
     * @author: liukezhu
     * @date : 2022/8/4
     * @return \json
     */
    public function getEnclosureList(){
        $value_id = $this->request->param('value_id',0,'int');
        $page = $this->request->param('page',0,'int');
        $limit = $this->request->param('limit',10,'int');
        if (empty($value_id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'village_id'=>$this->adminUser['village_id'],
                'value_id'=>$value_id,
                'page'=>$page,
                'limit'=>$limit
            ];
            $list = (new HousePublicRentalApplyService())->getEnclosureList($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 删除附件
     * @author: liukezhu
     * @date : 2022/8/4
     * @return \json
     */
    public function enclosureDel(){
        $id = $this->request->param('id',0,'int');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $list = (new HousePublicRentalApplyService())->enclosureDel($this->adminUser['village_id'],$id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$list){
            return api_output_error(1001,'数据不存在');
        }else{
            return api_output(0,$list);
        }
    }

    /**
     * 写入附件
     * @author: liukezhu
     * @date : 2022/8/4
     * @return \json
     */
    public function enclosureAddFile(){
        $template_id= $this->request->param('template_id',0,'int');
        $value_id= $this->request->param('value_id',0,'int');
        $file= $this->request->param('file');
        if (empty($template_id) || empty($value_id) || empty($file)){
            return api_output(1001,[],'缺少必要参数');
        }
        try {
            $param=[
                'village_id'=>$this->adminUser['village_id'],
                'template_id'=>$template_id,
                'value_id'=>$value_id,
                'file'=>$file
            ];
            $list = (new HousePublicRentalApplyService())->enclosureAddFile($param);
            return api_output(0,$list);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 办理入住
     * @author: liukezhu
     * @date : 2022/8/8
     * @return \json
     */
    public function getHandleApplyList(){
        $key= $this->request->param('key','','trim');
        $value = $this->request->param('value','','trim');
        $date= $this->request->param('date','','trim');
        $flow_status = $this->request->param('flow_status',0,'int');
        $page = $this->request->param('page',0,'int');
        $limit = $this->request->param('limit',10,'int');
        $handle_type= $this->request->param('handle_type','','trim');
        $handle_status= $this->request->param('handle_status','','trim');
        try{
            $param=[
                'type'=>1,
                'village_id'=>$this->adminUser['village_id'],
                'key'=>$key,
                'value'=>$value,
                'date'=>$date,
                'flow_status'=>$flow_status,
                'handle_type'=>$handle_type,
                'handle_status'=>$handle_status,
                'page'=>$page,
                'limit'=>$limit,
                'menus'=>$this->menus
            ];
            $list = (new HousePublicRentalApplyService())->getHandleApplyList($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     *办理入住用户详情
     * @author: liukezhu
     * @date : 2022/8/9
     * @return \json
     */
    public function getApplyRecordUserInfo(){
        $id = $this->request->param('id',0,'int');
        $source_type = $this->request->param('source_type',1,'int');
        try{
            $param=[
                'village_id'=>$this->adminUser['village_id'],
                'id'=>$id,
                'source_type'=>$source_type
            ];
            $list = (new HousePublicRentalApplyService())->getApplyRecordUserInfo($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 获取办理入住 处理内容
     * @author: liukezhu
     * @date : 2022/8/10
     * @return \json
     */
    public function getApplyRecordInfo(){
        $id = $this->request->param('id',0,'int');
        $source_type = $this->request->param('source_type',1,'int');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'village_id'=>$this->adminUser['village_id'],
                'id'=>$id,
                'source_type'=>$source_type
            ];
            $list = (new HousePublicRentalApplyService())->getApplyRecordInfo($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 获取操作记录列表
     * @author: liukezhu
     * @date : 2022/8/9
     * @return \json
     */
    public function getApplyRecordLog(){
        $id = $this->request->param('id',0,'int');
        $page = $this->request->param('page',0,'int');
        $limit = $this->request->param('limit',10,'int');
        $source_type = $this->request->param('source_type',1,'int');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'village_id'=>$this->adminUser['village_id'],
                'id'=>$id,
                'page'=>$page,
                'limit'=>$limit,
                'source_type'=>$source_type
            ];
            $list = (new HousePublicRentalApplyService())->getApplyRecordLog($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 办理入住 审核处理流程
     * @author: liukezhu
     * @date : 2022/8/10
     * @return \json
     */
    public function subOperationExamine(){
        $id = $this->request->param('id',0,'int');
        $single_id = $this->request->param('single_id',0,'int');
        $floor_id = $this->request->param('floor_id',0,'int');
        $layer_id= $this->request->param('layer_id',0,'int');
        $vacancy_id= $this->request->param('vacancy_id',0,'int');
        $examine_status= $this->request->param('examine_status',0,'int');
        $remarks= $this->request->param('remarks','','trim');
        $source_type = $this->request->param('source_type',1,'int');
        $log_imgs = $this->request->param('log_imgs');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'village_id'=>$this->adminUser['village_id'],
                'id'=>$id,
                'single_id'=>$single_id,
                'floor_id'=>$floor_id,
                'layer_id'=>$layer_id,
                'vacancy_id'=>$vacancy_id,
                'examine_status'=>$examine_status,
                'remarks'=>$remarks,
                'source'=>1,
                'user'=>$this->adminUser,
                'role'=>$this->login_role,
                'source_type'=>$source_type,
                'log_imgs'=>$log_imgs
            ];
            $list = (new HousePublicRentalApplyService())->operationExamineRecord($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 获取合同状态
     * @author: liukezhu
     * @date : 2022/8/29
     * @return \json
     */
    public function getContractStatus(){
        $id = $this->request->param('id',0,'int');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'village_id'=>$this->adminUser['village_id'],
                'id'=>$id
            ];
            $list = (new HousePublicRentalApplyService())->getContractStatus($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 获取合同列表
     * @author: liukezhu
     * @date : 2022/8/29
     * @return \json
     */
    public function getContractList(){
        $id = $this->request->param('id',0,'int');
        $page = $this->request->param('page',0,'int');
        $limit = $this->request->param('limit',10,'int');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $param=[
                'village_id'=>$this->adminUser['village_id'],
                'id'=>$id,
                'page'=>$page,
                'limit'=>$limit
            ];
            $list = (new HousePublicRentalApplyService())->getContractList($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }


    //====================================todo 办理退租 start======================================

    /**
     * 退租列表
     * @author: liukezhu
     * @date : 2022/9/14
     * @return \json
     */
    public function getRentingList(){

        $key= $this->request->param('key','','trim');
        $value = $this->request->param('value','','trim');
        $date= $this->request->param('date','','trim');
        $flow_status = $this->request->param('flow_status',0,'int');
        $page = $this->request->param('page',0,'int');
        $limit = $this->request->param('limit',10,'int');
        $handle_type= $this->request->param('handle_type','','trim');
        $handle_status= $this->request->param('handle_status','','trim');
        try{
            $param=[
                'village_id'=>$this->adminUser['village_id'],
                'key'=>$key,
                'value'=>$value,
                'date'=>$date,
                'flow_status'=>$flow_status,
                'handle_type'=>$handle_type,
                'handle_status'=>$handle_status,
                'page'=>$page,
                'limit'=>$limit,
                'menus'=>$this->menus
            ];
            $list = (new HousePublicRentalRentingService())->getRentingList($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     *删除退租申请
     * @author: liukezhu
     * @date : 2022/9/15
     * @return \json
     */
    public function rentingDel(){
        $id = $this->request->param('id',0,'int');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $list = (new HousePublicRentalRentingService())->rentingDel($this->adminUser['village_id'],$id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$list){
            return api_output_error(1001,'数据不存在');
        }else{
            return api_output(0,$list);
        }
    }


    /**
     * 查詢退租申请列表
     * @author:zhubaodi
     * @date_time: 2022/8/6 14:20
     */
    public function getCancelRentalList(){
        $key= $this->request->param('key','','trim');
        $value = $this->request->param('value','','trim');
        $date= $this->request->param('date','','trim');
        $status = $this->request->param('status',0,'int');
        $page = $this->request->param('page',0,'int');
        $limit = $this->request->param('limit',10,'int');
        try{
            $param=[
                'type'=>3,
                'village_id'=>$this->adminUser['village_id'],
                'key'=>$key,
                'value'=>$value,
                'date'=>$date,
                'status'=>$status,
                'page'=>$page,
                'limit'=>$limit
            ];
            $list = (new HousePublicRentalApplyService())->getApplyList($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }


    /**
     * 查询退租申请详情
     * @author:zhubaodi
     * @date_time: 2022/8/6 16:38
     */
    public function getCancelRentalInfo(){
        $data['village_id']=$this->adminUser['village_id'];
        $data['id'] = $this->request->param('id',0,'int');
        if (empty($data['id'])){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $list = (new HousePublicRentalApplyService())->getCancelRentalInfo($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$list){
            return api_output_error(1001,'数据不存在');
        }else{
            return api_output(0,$list);
        }
    }


    /**
     * 删除退租申请
     * @author:zhubaodi
     * @date_time: 2022/8/6 14:31
     */
    public function cancelRentalDel(){
        $id = $this->request->param('id',0,'int');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $list = (new HousePublicRentalApplyService())->applyDel($this->adminUser['village_id'],$id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$list){
            return api_output_error(1001,'数据不存在');
        }else{
            return api_output(0,$list);
        }
    }


    /**
     * 编辑排号规则
     * @author:zhubaodi
     * @date_time: 2022/8/6 14:33
     */
    public function addQueuingRule(){
        $data['village_id']=$this->adminUser['village_id'];
        $data['source_type']=$this->request->param('source_type',1,'intval');
        $data['title']=$this->request->param('title','','trim');
        $data['user_name']=$this->request->param('user_name','','trim');
        $data['user_phone']=$this->request->param('user_phone','','trim');
        $data['long']=$this->request->param('long','','trim');
        $data['lat']=$this->request->param('lat','','trim');
        $data['max_queue_number']=$this->request->param('max_queue_number',0,'intval');
        $data['content']=$this->request->param('content','','trim');
        $data['examine_type']=$this->request->param('examine_type',0,'intval');
        $data['work_start_time']=$this->request->param('work_start_time','','trim');
        $data['work_end_time']=$this->request->param('work_end_time','','trim');
        $data['adress']=$this->request->param('adress','','trim');
        $queuing_time=$this->request->param('queuing_time',[]);
        if (empty($data['title'])){
            return api_output(1001,[],'标题不能为空');
        }
        if (empty($data['user_name'])){
            return api_output(1001,[],'联系人不能为空');
        }
        if (empty($data['user_phone'])){
            return api_output(1001,[],'联系电话不能为空');
        }
        if (empty($data['long'])||empty($data['lat'])){
            return api_output(1001,[],'验房地址不能为空');
        }
        if (empty($data['max_queue_number'])){
            return api_output(1001,[],'每日最大预约数不能为空');
        }else{
            if ($data['max_queue_number'] < 0) {
                return api_output_error(1001, '每日最大预约数不能小于0');
            }
            if (floor($data['max_queue_number']) != $data['max_queue_number']) {
                return api_output_error(1001, '每日最大预约数请输入正整数');
            }
        }
        if (!$queuing_time || !isset($queuing_time[0]) || !isset($queuing_time[1])){
            return api_output(1001,[],'排号时间不能为空');
        }
        if($data['work_start_time'] && $data['work_end_time']){
            if($data['work_start_time'] >= $data['work_end_time']){
                return api_output(1001,[],'办公时间设置不合法，请重新选择');
            }
        }else{
            $data['work_start_time']=$data['work_end_time']='';
        }
        $data['queuing_start_time']=$queuing_time[0].' 00:00:00';
        $data['queuing_end_time']=$queuing_time[1].' 23:59:59';
        try{
            $res = (new HousePublicRentalApplyService())->addQueuingRule($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$res){
            return api_output_error(1001,'操作失败');
        }else{
            return api_output(0,$res);
        }

    }


    /**
     * 查询排号规则
     * @author:zhubaodi
     * @date_time: 2022/8/6 14:33
     */
    public function getQueuingRule(){
        $data['village_id']=$this->adminUser['village_id'];
        $data['source_type'] = $this->request->param('source_type',1,'int');
        try{
            $res = (new HousePublicRentalApplyService())->getQueuingRule($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }


    /**
     * 后台审核操作
     * @author:zhubaodi
     * @date_time: 2022/8/8 14:36
     */
    public function editCancelRentalstatus(){
        $data['village_id']=$this->adminUser['village_id'];
        $data['role_name']=isset($this->adminUser['adminName'])?$this->adminUser['adminName']:'';
        $data['role_phone']=isset($this->adminUser['phone'])?$this->adminUser['phone']:'';
        $data['role_id']=isset($this->adminUser['adminId'])?$this->adminUser['adminId']:$this->_uid;
        $data['id']=$this->request->param('id',0,'intval');
        $data['status']=$this->request->param('status',0,'intval');
        $data['content']=$this->request->param('content','','trim');
        $data['type']=3;
        try{
            $res = (new HousePublicRentalApplyService())->editCancelRentalstatus($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$res){
            return api_output_error(1001,'操作失败');
        }else{
            return api_output(0,$res);
        }

    }

}