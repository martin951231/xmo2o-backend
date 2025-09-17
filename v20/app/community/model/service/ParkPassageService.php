<?php

/**
 * Author lkz
 * Date: 2023/5/27
 * Time: 9:48
 */

namespace app\community\model\service;

use app\community\model\db\ParkPassage;
use app\community\model\db\ParkShowscreenLog;
use app\community\model\service\Park\ScreenHelperService;
use app\traits\ParkPassageHouseTraits;
use think\facade\Db;

class ParkPassageService
{
    use ParkPassageHouseTraits;

    /**
     * 需要计划任务执行 每三十分钟间隔 下发一次设备音量大小
     */
    public function syncDeviceVolumeJob(){
        $this->parkPassageFdump(['执行计划任务--'.__LINE__,],'parkPassage/syncDeviceVolumeJob',1);
        $parkPassage = new ParkPassage();
        $where = [
            ['park_sys_type' , 'in' , ['D3' , 'A11']],
            ['device_number', '<>', ''],
            ['device_setting', 'exp', Db::raw('IS NOT NULL')]
        ];
        $list = $parkPassage->getList($where,['id','village_id','device_number','device_setting']);
        if (!$list || $list->isEmpty()) {
            return true;
        }
        foreach ($list as $item){
            if(empty($item['device_setting'])){
                continue;
            }
            $queueData=[
                'id' => $item['id'],
                'village_id' => $item['village_id']
            ];
            $this->parkPassageQueuePushToJob($queueData);
        }
        return true;
    }

    /**
     * 设置设备音量.
     */
    public function syncDeviceVolume($id){

        $this->parkPassageFdump(['接收队列--'.__LINE__,['$id'=>$id]],'parkPassage/syncDeviceVolume',1);
        $screenService = new ScreenHelperService();
        $parkPassage = new ParkPassage();
        $parkShowscreenLog = new ParkShowscreenLog();
        $where = [
            ['id' , '=' , $id],
            ['park_sys_type' , 'in' , ['D3' , 'A11']],
            ['device_number', '<>', ''],
            ['device_setting', 'exp', Db::raw('IS NOT NULL')]
        ];
        $field = ['id','village_id','park_sys_type','park_type','device_number','device_setting'];
        $passageInfo = $parkPassage->getFind($where,$field);
        if (!$passageInfo || $passageInfo->isEmpty()) {
            return true;
        }
        $volume = 0;
        $unixNow = time();
        $setting = json_decode($passageInfo->device_setting,true);
        if(empty($setting)){
            return true;
        }
        foreach ($setting['volume'] as $item){
            $tmpStartUnix = strtotime(date('Y-m-d ') . $item['start']);
            $tmpEndUnix = strtotime(date('Y-m-d ') . $item['end']);
            if ($item['end'] == '00:00') {
                $tmpEndUnix += 86400;
            }
            if ($unixNow >= $tmpStartUnix && $unixNow < $tmpEndUnix) {
                $volume = $item['value'];
                break;
            }
        }
        $base64Data = $screenService->setScreenData('F0', $screenService->numToHex(dechex($volume), 2), 4);
        $sendData=[
            [
                'serialChannel' => 0,
                'data' => $base64Data,
                'dataLen' => strlen($base64Data),
            ]
        ];
        $logArr=[
            'village_id' => $passageInfo->village_id,
            'park_sys_type' => $passageInfo->park_sys_type,
            'showcontent' => json_encode($sendData,JSON_UNESCAPED_UNICODE),
            'car_number' => '',
            'duration' => '0',
            'price' => '0',
            'end_time' => 0,
            'channel_id' => $passageInfo->device_number,
            'park_type' => $passageInfo->park_type,
            'add_time' => $unixNow,
        ];
        fdump_api(['参数进来了=='.__LINE__,[
            '$volume' => $volume,
            '$sendData' => $sendData,
            '$setting' => $setting,
            '$logArr' => $logArr,
        ]],'parkPassage/syncDeviceVolumeArr',1);
        return $parkShowscreenLog->add($logArr);
    }


    /**
     * 获取设备音量  没有设置音量读取默认.
     */
    public function getParkDeviceSetting($village_id,$id){
        $parkPassage = new ParkPassage();
        $where = [
            ['id' , '=' , $id],
            ['village_id' , '=' , $village_id],
        ];
        $field = ['id','device_setting'];
        $passageInfo = $parkPassage->getFind($where,$field);
        $deviceSetting=[
          'volume'=>[
              [ 'start' => '00:00', 'end' => '08:00', 'value' => 4 , 'disable_start' => true , 'disable_end' => false],
              [ 'start' => '08:00', 'end' => '18:00', 'value' => 8 , 'disable_start' => false, 'disable_end' => false],
              [ 'start' => '18:00', 'end' => '00:00', 'value' => 4 , 'disable_start' => false, 'disable_end' => true ],
          ]
        ];
        if($passageInfo && !$passageInfo->isEmpty()){
            if(empty($passageInfo->device_setting)){
                $passageInfo->device_setting=json_encode($deviceSetting,JSON_UNESCAPED_UNICODE);
                $passageInfo->save();
            }else{
                $deviceSetting = json_decode($passageInfo->device_setting,true);
            }
        }
        return $deviceSetting;
    }

    /**
     * 校验音量参数.
     */
    public function checkDeviceSetting($param){
        if(!isset($param['volume']) || empty($param['volume'])){
            return true;
        }
        $volume=$param['volume'];
        $total = count($volume);
        foreach ($volume as $key=>$item){
            $j = $key+1;
            if(!strtotime($item['start'])){
                throw new \think\Exception('音量控制：第'.($j).'行开始时间设置错误');
            }
            if(!strtotime($item['end'])){
                throw new \think\Exception('音量控制：第'.($j).'行结束时间设置错误');
            }
            if($item['start'] > $item['end'] && ($j !== $total)){
                throw new \think\Exception('音量控制：第'.($j).'行时间设置存在错误');
            }
            if($key > 0 && $item['start'] < $volume[$key - 1]['end']){
                throw new \think\Exception('音量控制：第'.($j).'行开始时间应大于'.$volume[$key - 1]['end']);
            }
        }
        return true;
    }

}