<?php

/**
 * Date: 2023/5/27
 */

namespace app\job;

use app\community\model\service\ParkPassageService;
use think\Exception;
use think\queue\Job as DoJob;

class ParkPassageHouseJob
{

    public function fire(DoJob $job , $data)
    {
        if ($job->attempts() >= 1){
            $job->delete();
        }
        try {
            $dir_root=dirname(dirname(dirname(__FILE__)));
            $dir_root=dirname($dir_root);
            $basePath =  rtrim($dir_root,'/');
            $_SERVER['DOCUMENT_ROOT']=$basePath;
            
            $park_sys_type='';
            if($data && isset($data['park_sys_type']) && $data['park_sys_type']){
                $park_sys_type= $data['park_sys_type'];
            }
            if($park_sys_type=='HXT85'){
                $parkDevInfoHandleService=  new \app\community\model\service\Park\ParkDevInfoHandleService();
                $res = $parkDevInfoHandleService->batchMonthCarToDevStep($data);
            }else{
                if(isset($data['opt_type']) && $data['opt_type']=='get_third_ai_park_vehicle_status'){
                    $houseNewAiParkingService =  new \app\community\model\service\HouseNewAiParkingService();
                    $houseNewAiParkingService->getGaragePassageStatus($data['passage_id'],$data);
                }else if(isset($data['opt_type']) && $data['opt_type']=='update_third_ai_park_car'){
                    $houseNewAiParkingService =  new \app\community\model\service\HouseNewAiParkingService();
                    $houseNewAiParkingService->synUpdateThirdAiParkCar($data['car_id'],$data);
                }else if(isset($data['opt_type']) && $data['opt_type']=='update_garage_third_ai_park_car'){
                    $houseNewAiParkingService =  new \app\community\model\service\HouseNewAiParkingService();
                    $houseNewAiParkingService->synUpdateGarageThirdAiParkCar($data);
                } else{
                    (new ParkPassageService())->syncDeviceVolume($data['id']);
                } 
            }
            
        }catch (Exception $e){
            fdump_api(['_Line_' . __LINE__, 'data' => $data,'e'=>$e->getMessage(),'basePath'=>$basePath,'date'=>date('Y-m-d H:i:s')], 'job/parkPassageHouseJob', 1);
        }
        return true;
    }

    public function failed($data)
    {
    }
}