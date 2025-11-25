<?php

/**
 * Author lkz
 * Date: 2023/1/9
 * Time: 15:00
 */

namespace app\community\model\service;

use app\community\model\db\HouseFaceDevice;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageVacancyIccard;
use app\community\model\db\ProcessSubPlan;

class HouseVillageVacancyIccardService
{

    /**
     * @param $type  1：添加命令到设备 2：删除命令到设备
     * @param $param
     * @author : lkz
     * Date: 2023/1/9
     * Time: 17:12
     */
    public function getIcCardBindDevice($type,$param){
        $house_village_user_bind=new HouseVillageUserBind();
        $house_face_device=new HouseFaceDevice();
        $time=time();
        $user_bind=$house_village_user_bind->getOne([
            ['village_id','=',$param['village_id']],
            ['vacancy_id','=',$param['vacancy_id']],
            ['type','in',[0,3]],
            ['status','=',1],
        ],'pigcms_id');
        if (!$user_bind || $user_bind->isEmpty()){
            return true;
        }
        $user_bind=$user_bind->toArray();
        $where=[
            ['village_id','=',$param['village_id']],
            ['device_type','=',26],
            ['is_del','=',0],
        ];
        $device_list=$house_face_device->getList($where,'device_type,device_id,device_sn',1);
        if (!$device_list || $device_list->isEmpty()){
            return true;
        }
        $device_list=$device_list->toArray();
        $plan_data=[];
        foreach ($device_list as $v){
            $plan_data[]=[
                'param'=>serialize([
                    'type'=>'iccard_issued_device',
                    'param'=>[
                        'type'=>$type,
                        'village_id'=>$param['village_id'],
                        'vacancy_id'=>$param['vacancy_id'],
                        'ic_card'=>$param['ic_card'],
                        'device_id'=>$v['device_id'],
                        'pigcms_id'=>$user_bind['pigcms_id']
                    ]
                ]),
                'plan_time'     =>  -110,
                'space_time'    =>  0,
                'add_time'      =>  $time,
                'file'          =>  'sub_d5_park',
                'time_type'     =>  1,
                'unique_id'     =>  'addIcCardBind_'.$device_list['device_id'].'_'.$time.'_'.uniqid(),
                'rand_number'   =>  mt_rand(1, max(cfg('sub_process_num'), 3)),
            ];
        }
        if($plan_data){
            (new ProcessSubPlan())->addAll($plan_data);
        }
        return  true;
    }

    /**
     * 添加IC卡
     * @param $param
     * @return int|string
     * @author : lkz
     * Date: 2023/1/9
     * Time: 18:00
     */
    public function addIcCard($param){
        $HouseVillageVacancyIccard=new HouseVillageVacancyIccard();
        $id=$HouseVillageVacancyIccard->addOne($param);
        if($id){
            $time=time();
            $plan_data=[
                'param'=>serialize([
                    'type'=>'iccard_operation_device',
                    'param'=>[
                        'type'=>1,
                        'village_id'=>$param['village_id'],
                        'vacancy_id'=>$param['vacancy_id'],
                        'ic_card'=>$param['ic_card']
                    ]
                ]),
                'plan_time'     =>  -110,
                'space_time'    =>  0,
                'add_time'      =>  $time,
                'file'          =>  'sub_d5_park',
                'time_type'     =>  1,
                'unique_id'     =>  'addIcCard_'.$id.'_'.$time.'_'.uniqid(),
                'rand_number'   =>  mt_rand(1, max(cfg('sub_process_num'), 3)),
            ];
            (new ProcessSubPlan())->add($plan_data);
        }
        return $id;
    }

    /**
     * 删除IC卡
     * @param $village_id
     * @param $id
     * @return bool
     * @throws \think\Exception
     * @author : lkz
     * Date: 2023/1/9
     * Time: 18:06
     */
    public function delIcCard($village_id,$id){
        $HouseVillageVacancyIccard=new HouseVillageVacancyIccard();
        $ic_info=$HouseVillageVacancyIccard->getOne([
            ['village_id','=',$village_id],
            ['id','=',$id],
            ['status','=',1],
        ]);
        if (!$ic_info || $ic_info->isEmpty()){
            throw new \think\Exception("该记录不存在");
        }
        $result=$HouseVillageVacancyIccard->saveOne([
            ['id','=',$id]
        ],['status'=>4]);
        if(!$result){
            throw new \think\Exception("操作失败");
        }
        $time=time();
        $plan_data=[
            'param'=>serialize([
                'type'=>'iccard_operation_device',
                'param'=>[
                    'type'=>2,
                    'village_id'=>$ic_info['village_id'],
                    'vacancy_id'=>$ic_info['vacancy_id'],
                    'ic_card'=>$ic_info['ic_card']
                ]
            ]),
            'plan_time'     =>  -110,
            'space_time'    =>  0,
            'add_time'      =>  $time,
            'file'          =>  'sub_d5_park',
            'time_type'     =>  1,
            'unique_id'     =>  'delIcCard_'.$id.'_'.$time.'_'.uniqid(),
            'rand_number'   =>  mt_rand(1, max(cfg('sub_process_num'), 3)),
        ];
        (new ProcessSubPlan())->add($plan_data);
        return true;
    }

}