<?php
/**
 * @author : liukezhu
 * @date : 2021/11/17
 */
namespace app\community\controller\village_api\User;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\controller\manage_api\BaseController;
use app\community\model\service\HouseNewChargeService;
use app\community\model\service\StorageService;

class StorageApiController extends BaseController{

    /**
     * 预存类型
     * @author: liukezhu
     * @date : 2021/11/17
     * @return \json
     */
    public function getType(){
        $village_id = $this->request->post('village_id',0,'intval');
        try{
            $data = (new StorageService())->getType($village_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }
    /***
    * 检查缴费情况
     **/
    public function checkUserOrderPaymentStatus(){
        $village_id = $this->request->post('village_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');  //住户身份
        $uid = $this->request->post('uid',0,'intval');
        $type = $this->request->post('type',0,'trim'); /**water,hotwater,electric,villagebalance**/
        try{
            $storageService=new StorageService();
            $parameter=array('pigcms_id'=>$pigcms_id,'uid'=>$uid,'type'=>$type);
            $ret= $storageService->checkUserOrderPaymentStatus($village_id,$parameter);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$ret);
    }
    /**
     * 预存记录
     * @author: liukezhu
     * @date : 2021/11/17
     * @return \json
     */
    public function getIndex(){
        $village_id = $this->request->post('village_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        $page = $this->request->post('page',1,'intval');
        $limit = $this->request->post('limit',10,'intval');
        if(!$village_id || !$pigcms_id || !$this->request->log_uid)
            return api_output_error(1001,'缺少必要参数');
        $source = ['0','village_water','village_electric','village_gas'];
        $jz_wsxdsj_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if($jz_wsxdsj_customized_open){
            $source[] = 'village_qunuanfei';
        }
        $where[] = ['uid','=',$this->request->log_uid];
        $where[] = ['business_type','=',2];
        $where[] = ['business_id','=',$village_id];
        $where[] = ['paid','=',1];
        $where[] = ['source','in',$source];
        $where[] = ['pay_time', '>', 0];
        $field = 'order_id,pay_time,label,money';
        try{
            $data = (new StorageService())->getRecord($where,$field,$page,$limit,'order_id DESC');
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     *获取预存
     * @author: liukezhu
     * @date : 2021/11/17
     * @return \json
     */
    public function choiceStorage(){
        $type = $this->request->post('type','');
        $village_id = $this->request->post('village_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        $is_new = $this->request->post('is_new',0,'intval');  //新版
        if(!$type || !$village_id || !$pigcms_id || !$this->request->log_uid)
            return api_output_error(1001,'缺少必要参数');
        try{
            $data = (new StorageService())->getStorage($pigcms_id,$this->request->log_uid,$type,$village_id,$is_new);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 预存详情
     * @author: liukezhu
     * @date : 2021/11/17
     * @return \json
     */
    public function getStorageDetails(){
        $order_id = $this->request->post('order_id',0,'intval');
        $village_id = $this->request->post('village_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        if(!$village_id || !$pigcms_id || !$this->request->log_uid)
            return api_output_error(1001,'缺少必要参数');
        try{
            $data = (new StorageService())->getStorageDetails($order_id,$village_id,$pigcms_id,$this->request->log_uid);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }


    /**
     * 取消、开通自动扣款
     * @author: liukezhu
     * @date : 2021/11/17
     * @return \json
     */
    public function userAutomaticPay(){
        $type = $this->request->post('type',1,'intval');
        $storage_type = $this->request->post('storage_type','','trim');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        if(!$this->request->log_uid || !$pigcms_id || !$storage_type)
            return api_output_error(1001,'缺少必要参数');
        try{
            $data = (new StorageService())->userSet($this->request->log_uid,$pigcms_id,$type,$storage_type);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 订单打印模板
     * @author: liukezhu
     * @date : 2021/11/23
     * @return \json
     */
    public function printTemplate(){
        $order_id = $this->request->post('order_id',0,'intval');
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        if(!$this->request->log_uid || !$pigcms_id || !$order_id)
            return api_output_error(1001,'缺少必要参数');
        try{
            $data = (new StorageService())->printRechargeOrder($this->request->log_uid,$order_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    public function  getVillageUserMoney(){
        $uid=$this->request->log_uid;
        $pigcms_id = $this->request->post('pigcms_id',0,'intval');
        $village_id = $this->request->post('village_id',0,'intval');
        $charge_type = $this->request->post('charge_type','','trim');
        if($uid<1 || $village_id<1){
            return api_output_error(1001,'缺少必要参数');
        }
        try{
            $new_charge_type='';
            if($charge_type && strpos($charge_type,'village_water_')!==false){
                $new_charge_type='cold_water_balance';
            }else if($charge_type && strpos($charge_type,'village_hotwater_')!==false){
                $new_charge_type='hot_water_balance';
            }else if($charge_type && strpos($charge_type,'village_electric_')!==false){
                $new_charge_type='electric_balance';
            }else if($charge_type && strpos($charge_type,'village_villagebalance_')!==false){
                $new_charge_type='village_balance';
            }
            $storageService=new StorageService();
            $ret = $storageService->getVillageUser($uid,$village_id,$new_charge_type);
            return api_output(0,$ret);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        
    }
}