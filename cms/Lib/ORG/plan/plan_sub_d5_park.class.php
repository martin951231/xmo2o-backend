<?php
/*
 *D5停车设备
 * */
class plan_sub_d5_park extends plan_base{
    public function runTask($param){
        
        //删除计划任务
        if($param['plan_task_id']){
            $this->dropPlan($param['plan_task_id']);
        }
        $this->keepThread();
        if(isset($param['type']) && !empty($param['type'])){
            if(in_array($param['type'],['in_park','out_park'])){
                $garage_id=0;
                if(isset($param['garage_id']) && $param['garage_id']>0){
                    $garage_id=$param['garage_id'];
                }
                invoke_v20_service('\app\community\model\service\Park\D5Service/pullCarPark',[
                    'type'          =>  $param['type'],
                    'village_id'    =>  $param['village_id'],
                    'config'        =>  $param['config'],
                    'start'         =>  $param['start'],
                    'end'           =>  $param['end'],
                    'pageNum'       =>  $param['pageNum'],
                    'pageSize'      =>  $param['pageSize'],
                    'garage_id'      =>  $garage_id
                ]);
            }elseif ($param['type'] == 'download_pic'){
                invoke_v20_service('\app\community\model\service\Park\D5Service/synPicRecord',[
                    'village_id'  =>  $param['village_id'],
                    'record_id'   =>  $param['record_id'],
                    'data'        =>  $param['data']
                ]);
            }elseif ($param['type'] == 'd6_syn_single_car'){
                invoke_v20_service('\app\community\model\service\Park\D6Service/d6SynSingle',['param'=>$param]);
            }elseif ($param['type'] == 'customize_water_reading'){ //定制水点燃表 执行生成订单
                invoke_v20_service('\app\community\model\service\CustomizeMeterWaterReadingService/createOrder',[
                    'param'  =>  $param,
                ]);
            }elseif ($param['type'] == 'customize_water_type'){ //定制水表 执行参数更新
                invoke_v20_service('\app\community\model\service\CustomizeMeterWaterReadingService/updateWaterType',[
                    'village_id'  =>  $param['village_id'],
                    'company_name'   =>  $param['company_name'],
	                ]);
            }elseif ($param['type'] == 'park_month_notice'){
                invoke_v20_service('\app\community\model\service\HouseNewParkingService/sendParkMonthExpiresNotice',[
                    'village_id'  =>  $param['village_id']
                ]);
            }elseif ($param['type'] == 'org_del_sys_face_door'){ //组织架构删除人员 同步删除设备数据
                invoke_v20_service('\app\community\model\service\PropertyFrameworkService/synHandleUserRelation',[
                    'type'  =>  1,
                    'param'=>$param['param']
                ]);
            }elseif ($param['type'] == 'org_del_sys_wechat'){ //组织架构删除人员 同步企业微信
                invoke_v20_service('\app\community\model\service\PropertyFrameworkService/synHandleUserRelation',[
                    'type'  =>  2,
                    'param'=>$param['param']
                ]);
            }elseif ($param['type'] == 'area_street_send_notice'){//街道新闻 通知业主
                D('Area_street_news')->implement_send_notice($param['userInfo'],$param['param']);
            }elseif ($param['type'] == 'iccard_operation_device'){ //IC卡组装数据 批量转单个下发
                invoke_v20_service('\app\community\model\service\HouseVillageVacancyIccardService/getIcCardBindDevice',[
                    'type'  =>  $param['param']['type'],
                    'param'=>$param['param']
                ]);
            }elseif ($param['type'] == 'iccard_issued_device'){ //IC卡下发命令到设备
                if($param['param']['type'] == 1){//下发添加命令
                    D('Face_d6_request_record')->add_create_card_record($param['param']['ic_card'],$param['param']['device_id'],$param['param']['pigcms_id'],$param['param']['village_id']);
                }elseif ($param['param']['type'] == 2){//下发删除命令
                    D('Face_d6_request_record')->del_card_record($param['param']['ic_card'],$param['param']['device_id'],$param['param']['pigcms_id'],$param['param']['village_id']);
                }
            }

        }
        return true;
    }
}
?>