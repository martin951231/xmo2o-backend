<?php


namespace app\new_marketing\controller\api;


use app\new_marketing\model\service\HousePropertyService;

class HousePropertyController extends ApiBaseController
{
    //物业详情
   public function housePropertyDetail()
   {
       $houseId = $this->request->param('houseId', 0, 'intval');//物业id
       if(empty($houseId)){
           return api_output_error(1003,"没有获取到物业信息");
       }
       $ret=(new HousePropertyService())->housePropertyDetail($houseId);
       return api_output(0, $ret, '获取成功');
   }

    /**
     * 业务经理/业务员我的物业列表
     */
   public function personHousePropertyList(){
       try{
           $uid =$this->request->log_uid??0;
           $page = $this->request->param('page',1,'intval');//页数
           $pageSize = $this->request->param('pageSize',10, 'intval');//每页数量
           if($uid==0){
               return api_output_error(1002,"获取用户信息失败,请重新登录");
           }
           $identity = $this->request->param('identity', 0, 'intval');//0业务人员 1业务经理
           $person_id = $this->request->param('person_id', 0, 'intval');//业务人员id
           $msg=(new HousePropertyService())->personManagerHousePropertyList($uid,$identity,$person_id,$page,$pageSize);
           return api_output(0, $msg, '获取成功');
       }catch(\Exception $e){
           return api_output_error(1003, $e->getMessage());
       }
   }

}