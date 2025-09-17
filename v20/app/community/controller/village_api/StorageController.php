<?php
/**
 * @author : liukezhu
 * @date : 2021/11/18
 */
namespace app\community\controller\village_api;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseNewMeterService;
use app\community\model\service\StorageService;
use app\community\model\service\HouseNewChargeService;

class StorageController extends CommunityBaseController{

    /**
     * 用户列表
     * @author: liukezhu
     * @date : 2021/11/18
     * @return \json
     */
    public function getUserList(){
        $customized_meter_reading=cfg('customized_meter_reading');
        $customized_meter_reading=!empty($customized_meter_reading) ? intval($customized_meter_reading):0;
        if($customized_meter_reading<1 && intval(cfg('cockpit'))){
           return $this->getUserList_old();
        }
        $data['page'] = $this->request->param('page',0,'int');
        $data['name']= $this->request->param('name','','trim');
        $data['phone']= $this->request->param('phone','','trim');
        $data['uid']= $this->request->param('uid','');
        $data['village_id'] =  $this->adminUser['village_id'];
        $data['limit']=$this->request->param('limit',20,'int');
        $data['vacancy_arr']=$this->request->param('vacancy','');
        $data['bind_pigcms_id']=$this->request->param('bind_pigcms_id',0,'intval');
        try{
            $storageService=new StorageService();
            $list = $storageService->getUserList($data,true);
            $list['is_customized_meter_reading']=$customized_meter_reading;
            $list['meter_extended_data']='';
            $list['charge_type_arr']=array();
            if($customized_meter_reading>0){
                $serviceHouseNewPorperty = new HouseNewChargeService();
                $list['meter_extended_data']=$storageService->getMeterExtendedData($data['village_id']);
                $list['charge_type_arr']=$serviceHouseNewPorperty->charge_type_arr;
            }
            
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }


    public function getUserList_old(){
        $page = $this->request->param('page',0,'int');
        $name= $this->request->param('name','','trim');
        $phone= $this->request->param('phone','','trim');
        $property_id =  $this->adminUser['property_id'];
        try{
            $where[]=['ub.village_id','=',$this->adminUser['village_id']];
            $where[]=['ub.uid','>',0];
            $where[]=['ub.type', 'in', '0,3'];
            $where[]=['ub.status', '=', 1];
            $where[]=['ub.vacancy_id','>',0];
            if(!empty($name)){
                $where[] = ['ub.name', 'like', '%'.$name.'%'];
            }
            if(!empty($phone)){
                $where[] = ['ub.phone', 'like', '%'.$phone.'%'];
            }
            $field='ub.pigcms_id,ub.uid,ub.name,ub.phone,u.now_money,(SELECT count(distinct b.vacancy_id )  FROM pigcms_house_village_user_bind AS b  WHERE b.uid = ub.uid AND b.village_id = ub.village_id AND b.STATUS = 1 AND b.vacancy_id > 0 AND b.uid > 0 AND b.type IN ( 0, 1, 2, 3 ) ) as room_num,	(
	SELECT
		count(p.position_id) 
	FROM
		pigcms_house_village_bind_position AS bp 
	RIGHT JOIN pigcms_house_village_parking_position as p on p.position_id = bp.position_id
	WHERE
		bp.village_id = ub.village_id 
		AND bp.user_id IN ( group_concat( ub.pigcms_id ) ) 
	) AS position_num';
            $order='ub.pigcms_id desc';
            $list = (new StorageService())->getUserList_old($where,$field,$order,$page,10);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     *获取用户余额
     * @author: liukezhu
     * @date : 2021/11/19
     * @return \json
     */
    public function getUserBalance(){
        if(intval(cfg('cockpit'))){
            return $this->getUserBalance_old();
        }
        $uid = $this->request->param('uid','');
        $village_id =  $this->adminUser['village_id'];
        if (empty($uid)){
            return api_output(1001,[],'缺少必要参数');
        }
        if(is_array($uid)){
            $uid=$uid['0'];
        }
        $uid=intval($uid);
        try{
            $list = (new StorageService())->getVillageUser($uid,$village_id);
            $configCustomizationService=new ConfigCustomizationService();
            $is_aihorse_customized=$configCustomizationService->getAihorseCustomizedJudge();
            $list['is_aihorse_customized']=$is_aihorse_customized;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$list){
            return api_output_error(1001,'数据不存在');
        }else{
            return api_output(0,$list);
        }
    }
    public function getUserBalance_old(){
        $pigcms_id = $this->request->param('pigcms_id','','intval');
        if (empty($pigcms_id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $where[]=['hvb.pigcms_id', '=',$pigcms_id];
            $where[]=['hvb.village_id','=',$this->adminUser['village_id']];
            $where[]=['hvb.status', '=', 1];
            $field='pigcms_id,u.now_money';
            $list = (new StorageService())->getUser($where,$field);
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
     * 用户余额变更
     * @author: liukezhu
     * @date : 2021/11/19
     * @return \json
     */
    public function userBalanceChange_old(){
        $pigcms_id = $this->request->param('pigcms_id',0,'intval');
        $status = $this->request->param('status',0,'intval');
        $price = $this->request->param('price','','trim');
        $remarks = $this->request->param('remarks','','trim');
        if (!$pigcms_id){
            return api_output(1001,[],'缺少必要参数');
        }
        if(empty($status)){
            return api_output(1001,[],'请选择状态');
        }
        if(empty($price) || !is_numeric($price)){
            return api_output(1001,[],'请输入缴费金额');
        }
        $configCustomizationService=new ConfigCustomizationService();
        $is_aihorse_customized=$configCustomizationService->getAihorseCustomizedJudge();
        if($is_aihorse_customized<1 && empty($remarks)){
            return api_output(1001,[],'请输入备注');
        }
        try{
            $system_remarks=(new StorageService())->getRoleData($this->_uid,$this->login_role,$this->adminUser);
            $id = (new StorageService())->userBalanceChange1($pigcms_id,$status,$price,$system_remarks['remarks'],$remarks);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$id['error']){
            return api_output_error(1001,$id['msg']);
        }else{
            return api_output(0,$id['msg']);
        }
    }

    public function userBalanceChange(){
        if(intval(cfg('cockpit'))){
            return $this->userBalanceChange_old();
        }
        $pigcms_id = $this->request->param('uid',0,'intval');
        $status = $this->request->param('status',0,'intval');  //1增加 2减少
        $price = $this->request->param('price','','trim');
        $remarks = $this->request->param('remarks','','trim');
        $opt_money_type=$this->request->param('opt_money_type','','trim');
        $off_pay_type=$this->request->param('off_pay_type',0,'intval');  //线下支付方式
        $village_id =  $this->adminUser['village_id'];
        if (!$pigcms_id){
            return api_output(1001,[],'缺少必要参数');
        }
        if(empty($status)){
            return api_output(1001,[],'请选择状态');
        }
        if(empty($price) || !is_numeric($price)){
            return api_output(1001,[],'请输入缴费金额');
        }
        $configCustomizationService=new ConfigCustomizationService();
        $is_aihorse_customized=$configCustomizationService->getAihorseCustomizedJudge();
        if($is_aihorse_customized<1 && empty($remarks)){
            return api_output(1001,[],'请输入备注');
        }
        if($status!=1){
            $off_pay_type=0;
        }
        $extra_data=array('off_pay_type'=>$off_pay_type,'login_role'=>$this->login_role,'login_uid'=>$this->_uid,'opt_name'=>$this->adminUser['user_name']);
        if($status==1){
            $extra_data['order_type']='pc_plus';
        }else{
            $extra_data['order_type']='pc_minus';
        }
        try{
            $system_remarks=(new StorageService())->getRoleData($this->_uid,$this->login_role,$this->adminUser);
            $ret = (new StorageService())->userBalanceChange($pigcms_id,$status,$price,$system_remarks['remarks'],$remarks,0,$village_id,$opt_money_type,$extra_data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$ret['error']){
            return api_output_error(1001,$ret['msg']);
        }else{
            return api_output(0,$ret);
        }
    }
    
    
    /**
     * 余额记录
     * @author: liukezhu
     * @date : 2021/11/22
     * @return \json
     */
    public function getUserBalanceRecord_old(){
        $page = $this->request->param('page',0,'int');
        $uid = $this->request->param('uid',0,'intval');
        $fee_type = $this->request->param('fee_type',0,'intval'); //0全部的1预存2抵扣
        $date = $this->request->param('date',''); //预存时间
        $name = $this->request->param('name','','trim'); //姓名
        $phone = $this->request->param('phone','','trim'); //手机号
        try{
            $where=array();
            $where[]=['ml.village_id','=',$this->adminUser['village_id']];
            if($uid>0){
                $where[]=['ml.uid', '=',$uid];
            }
            if($fee_type==1){
                $where[]=['ml.type', '=',1];
            }else if($fee_type==2){
                $where[]=['ml.type', '=',2];
            }
            if(!empty($name)){
                $where[]=['u.nickname', 'like','%'.$name.'%'];
            }
            if(!empty($phone)){
                $where[]=['u.phone', 'like','%'.$phone.'%'];
            }
            if(!empty($date)){
                if (!empty($date[0])) {
                    $where[] = ['ml.time', '>=', strtotime($date[0] . ' 00:00:00')];
                }
                if (!empty($date[1])) {
                    $where[] = ['ml.time', '<=', strtotime($date[1] . ' 23:59:59')];
                }
            }
              $where[]=['ml.ask','in',[20,21]];
              $field='order_no,money,type,time as add_time,desc,current_money,after_price,u.nickname as name,u.phone';
              $list = (new StorageService())->getUerBalanceRecord_old($where,$field,$page,10);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$list){
            return api_output_error(1001,'数据不存在');
        }else{
            return api_output(0,$list);
        }
    }

    public function getUserBalanceRecord(){
        $customized_meter_reading=cfg('customized_meter_reading');
        $customized_meter_reading=!empty($customized_meter_reading) ? intval($customized_meter_reading):0;
        if($customized_meter_reading<1 && intval(cfg('cockpit'))){
            return $this->getUserBalanceRecord_old();
        }
        $page = $this->request->param('page',0,'int');
        $uid = $this->request->param('uid',0,'intval');
        $village_id = $this->request->param('village_id','','intval');
        $money_type = $this->request->param('money_type',0,'intval'); //0全部的1物业费2电费3热水4冷水
        $fee_type = $this->request->param('fee_type',0,'intval'); //0全部的1预存2抵扣
        $date = $this->request->param('date',''); //预存时间
        $name = $this->request->param('name','','trim'); //姓名
        $phone = $this->request->param('phone','','trim'); //手机号
        $off_pay_type = $this->request->param('off_pay_type',0,'intval'); //线下支付方式
        try{
            if (empty($village_id)){
                $village_id=$this->adminUser['village_id'];
            }
            $where=array();
            $where[]=['ml.business_id','=',$village_id];
            $where[]=['ml.business_type', '=',1];
            if($uid>0){
                $where[]=['ml.uid', '=',$uid];
            }
            if($money_type>0){
                $money_type=$money_type-1;
                $where[]=['ml.money_type', '=',$money_type];
            }
            if($fee_type==1){
                $where[]=['ml.type', '=',1];
            }else if($fee_type==2){
                $where[]=['ml.type', '=',2];
            }
            if(!empty($name)){
                $where[]=['u.nickname', 'like','%'.$name.'%'];
            }
            if(!empty($phone)){
                $where[]=['u.phone', 'like','%'.$phone.'%'];
            }
            if(!empty($date)){
                if (!empty($date[0])) {
                    $where[] = ['ml.add_time', '>=', strtotime($date[0] . ' 00:00:00')];
                }
                if (!empty($date[1])) {
                    $where[] = ['ml.add_time', '<=', strtotime($date[1] . ' 23:59:59')];
                }
            }
            if($off_pay_type>0){
                $where[]=['ml.off_pay_type', '=',$off_pay_type];
            }
            $field='ml.*,u.nickname as name,u.phone';
            $list = (new StorageService())->getUerBalanceRecord($village_id,$where,$field,$page,10);
            $list['prestore_money_show'] = 0;
            $list['deduction_money_show'] = 0;
            $list['now_money_show'] = 0;
            if(in_array($fee_type,[0,1])){
                //预存总金额
                $prestoreWhere = $where;
                $prestoreWhere[] = ['ml.type', '=',1];
                $list['prestore_money'] = (new StorageService())->getVillageUserMoney($prestoreWhere);
                $list['prestore_money'] = getFormatNumber($list['prestore_money']);
                $list['prestore_money_show'] = 1;
            }
            if(in_array($fee_type,[0,2])){
                //抵扣总金额
                $deductionWhere = $where;
                $deductionWhere[] = ['ml.type', '=',2];
                $list['deduction_money'] = (new StorageService())->getVillageUserMoney($deductionWhere);
                $list['deduction_money'] = getFormatNumber($list['deduction_money']);
                $list['deduction_money_show'] = 1;
            }
            //小区住户余额总额(物业余额)
            $list['now_money'] = (new StorageService())->getVillageUserMoney(['business_id'=>$village_id],1);
            $list['now_money'] = getFormatNumber($list['now_money']);
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
     *消费记录
     * @author: liukezhu
     * @date : 2021/11/22
     * @return \json
     */
    public function getUserOrderRecord_old(){
        $page = $this->request->param('page',0,'int');
        $uid = $this->request->param('uid','','intval');
        if (empty($uid)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $list = (new StorageService())->getOrderRecord_old($this->adminUser['village_id'],$uid,$page,10);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$list){
            return api_output_error(1001,'数据不存在');
        }else{
            return api_output(0,$list);
        }
    }

    public function getUserOrderRecord(){
        if(intval(cfg('cockpit'))){
            return $this->getUserOrderRecord_old();
        }
        $page = $this->request->param('page',0,'int');
        $uid = $this->request->param('uid','','intval');
        if (empty($uid)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $list = (new StorageService())->getOrderRecord($this->adminUser['village_id'],$uid,$page,10);
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
     * 一键催缴
     * @author: liukezhu
     * @date : 2021/11/22
     * @return \json
     */
    public function sendMessage(){
        $order_id = $this->request->param('order_id',0,'int');
        if (empty($order_id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $list = (new StorageService())->sendMessage($this->adminUser['village_id'],$order_id);
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
     * 查询用户在当前小区的关联房间列表
     * @author:zhubaodi
     * @date_time: 2022/6/8 17:40
     */
    public function getUserRoomList()
    {
        $village_id=$this->adminUser['village_id'];
        $uid = $this->request->param('uid',0,'intval');
        $page = $this->request->param('page',1,'intval');
        $limit=10;
        if (empty($uid)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $list = (new StorageService())->getUserRoomList($uid,$village_id,$page,$limit);
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
     * 查询用户在当前小区的关联车位列表
     * @author:zhubaodi
     * @date_time: 2022/6/8 17:40
     */
    public function getUserPositionList()
    {
        $village_id=$this->adminUser['village_id'];
        $uid = $this->request->param('uid',0,'intval');
        if (empty($uid)){
            return api_output(1001,[],'缺少必要参数');
        }
        $page = $this->request->param('page',1,'intval');
        $limit=10;
        try{
            $list = (new StorageService())->getUserPositionList($uid,$village_id,$page,$limit);
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
     * 批量修改住户余额
     * @author:zhubaodi
     * @date_time: 2022/6/8 18:54
     */
    public function addAllVillageUserMoney(){
        $data=array();
        $data['village_id']=$this->adminUser['village_id'];
        $data['uid'] = $this->request->param('uid','');
        $data['current_village_balance'] = $this->request->param('price','','trim');
        $data['type'] = $this->request->param('status',0,'intval');
        $data['desc'] = $this->request->param('remarks','','trim');
        $data['opt_money_type'] = $this->request->param('opt_money_type','','trim');
        $data['bind_pigcms_id']= $this->request->param('bind_pigcms_id',0,'intval');
        $off_pay_type=$this->request->param('off_pay_type',0,'intval');  //线下支付方式
        if (empty($data['uid'])){
            return api_output(1001,[],'缺少必要参数');
        }
        if(empty($data['type'])){
            return api_output(1001,[],'请选择状态');
        }
        if(empty($data['current_village_balance']) || !is_numeric($data['current_village_balance'])){
            return api_output(1001,[],'请输入缴费金额');
        }
        $configCustomizationService=new ConfigCustomizationService();
        $is_aihorse_customized=$configCustomizationService->getAihorseCustomizedJudge();
        if($is_aihorse_customized<1 && empty($data['desc'])){
            return api_output(1001,[],'请输入备注');
        }
        if($data['type']!=1){
            $off_pay_type=0;
        }
        $extra_data=array('off_pay_type'=>$off_pay_type,'login_role'=>$this->login_role,'login_uid'=>$this->_uid,'opt_name'=>$this->adminUser['user_name']);
        if($data['type']==1){
            $extra_data['order_type']='pc_plus';
        }else{
            $extra_data['order_type']='pc_minus';
        }
        $data['extra_data']=$extra_data;
        try{
            if(in_array($this->login_role,[6,7])){
                $data['role_id']=0;
            }else{
                $data['role_id']=$this->_uid;
            }
            $ret = (new StorageService())->addAllVillageUserMoney($data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($ret['error_code']){
            return api_output_error(1001,$ret['msg']);
        }else{
            return api_output(0,$ret);
        }
    }

    /**
     * 导入抄表
     * @return \json
     */
    public function uploadFiles()
    {
        $file = $this->request->file('file');
        if(!$file){
            return api_output_error(1001,'请上传文件');
        }
        try {
            validate(['file' => [
                'fileSize' => 1024 * 1024 * 20,
                'fileExt' => 'xls,xlsx',
            ]])->check(['file' => $file]);

            $fileName = $file->getOriginalName();

            $file_arr = explode('.',$fileName);
            if(count($file_arr)==2)
            {
                $file_type = $file_arr[1];
            }else{
                return api_output_error(1001,'请上传有效文件');
            }

            $savename = \think\facade\Filesystem::disk('public_upload')->putFile( 'villageUserMoney/file',$file);
            if(strpos($savename,"\\") !== false){
                $savename = str_replace('\\','/',$savename);
            }
            $data['url'] = '/upload/'.$savename;
            $data['name'] = $fileName;
            $data['file_type'] = $file_type;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");

    }

    /**
     * 提交抄表表格
     * @return \json
     */
    public function exportMeter()
    {
        $village_id = $this->adminUser['village_id'];
        $uid = $this->_uid;
        $service = new StorageService();
        $file = $this->request->post('file');
        try {
            $savenum = $service->upload($file,$village_id,$uid);
            return api_output(0, $savenum, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    /***
    *导出余额日志记录
     **/
    public function excelExportBalanceRecord(){
        set_time_limit(0);
        $uid = $this->request->param('uid','','intval');
        $village_id=$this->adminUser['village_id'];
        $money_type = $this->request->param('money_type','0','intval'); //0全部的1物业费2电费3热水4冷水
        $fee_type = $this->request->param('fee_type',0,'intval'); //0全部的1预存2抵扣
        $date = $this->request->param('date',''); //预存时间
        $name = $this->request->param('name','','trim'); //姓名
        $phone = $this->request->param('phone','','trim'); //手机号
        $where=array();
        $where[]=['ml.business_id','=',$village_id];
        $where[]=['ml.business_type', '=',1];
        if($uid>0){
            $where[]=['ml.uid', '=',$uid];
        }
        if($money_type>0){
            $money_type=$money_type-1;
            $where[]=['money_type', '=',$money_type];
        }
        
        if($fee_type==1){
            $where[]=['ml.type', '=',1];
        }else if($fee_type==2){
            $where[]=['ml.type', '=',2];
        }
        if(!empty($name)){
            $where[]=['u.nickname', 'like','%'.$name.'%'];
        }
        if(!empty($phone)){
            $where[]=['u.phone', 'like','%'.$phone.'%'];
        }
        if(!empty($date)){
            if (!empty($date[0])) {
                $where[] = ['ml.add_time', '>=', strtotime($date[0] . ' 00:00:00')];
            }
            if (!empty($date[1])) {
                $where[] = ['ml.add_time', '<=', strtotime($date[1] . ' 23:59:59')];
            }
        }
        try{
            $storageService=new StorageService();
            $list = $storageService->excelExportBalanceRecord($where,$uid,$village_id);
            return api_output(0,$list);
         }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function exportUserBalanceRecord(){
        $data=array();
        $data['name']= $this->request->param('name','','trim');
        $data['phone']= $this->request->param('phone','','trim');
        $data['village_id'] =  $this->adminUser['village_id'];
        try{
            $list = (new StorageService())->exportUserBalanceRecord($data);
            return api_output(0,$list);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function storageLimitBalanceTips(){
        $data=array();
        $data['village_id'] =  $this->adminUser['village_id'];
        $data['cold_water_balance'] = $this->request->param('cold_water_balance','');
        $data['hot_water_balance'] = $this->request->param('hot_water_balance','');
        $data['electric_balance'] = $this->request->param('electric_balance','');
        try{
            $list = (new StorageService())->setMeterExtendedData($data,$data['village_id'],'balance');
            return api_output(0,$list);
        }catch(\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function storageFeePrestoreSet(){
        $data=array();
        $data['village_id'] =  $this->adminUser['village_id'];
        $data['cold_water_prestore'] = $this->request->param('cold_water_prestore','');
        $data['electric_prestore'] = $this->request->param('electric_prestore','');
        $data['gas_prestore'] = $this->request->param('gas_prestore','');
        $data['hot_water_prestore'] = $this->request->param('hot_water_prestore','');
        $data['property_fee_pay_first'] = $this->request->param('property_fee_pay_first','');
        try{
            $list = (new StorageService())->setMeterExtendedData($data,$data['village_id'],'prestore');
            return api_output(0,$list);
        }catch(\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function getPrintInfo(){
        $village_id =  $this->adminUser['village_id'];
        $idd= $this->request->post('idd',0,'intval');
        $uid= $this->request->post('uid',0,'intval');
        try{
            if($idd<1){
                return api_output_error(1001,'数据参数错误！');
            }
            $list = (new StorageService())->getPrintInfo($village_id,$idd,$uid);
            if(empty($list)){
                return api_output_error(1001,'没有查找到打印的数据！');
            }
            return api_output(0,$list);
        }catch(\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
}