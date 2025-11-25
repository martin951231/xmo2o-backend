<?php
/**
 * @author : liukezhu
 * @date : 2022/10/25
 */

namespace app\traits;

use app\job\MeterDirectorNoticeHouseJob;
use think\facade\Queue;


trait MeterDirectorNoticeHouseTraits
{

    /**
     * 队列名称
     * @author: liukezhu
     * @date : 2022/10/25
     * @return string
     */
    public function meterDirectorNoticeHouseQueueName()
    {
        return 'meter-director-notice-house';
    }

    /**
     * 立即下发队列
     * @author: liukezhu
     * @date : 2022/10/25
     * @param $queueData
     * @return mixed
     */
    protected function meterDirectorNoticeQueuePushToJob($queueData)
    {
        return  Queue::push(MeterDirectorNoticeHouseJob::class,$queueData,$this->meterDirectorNoticeHouseQueueName());
    }


    //临时打印日志
    public function meterDirectorNoticeFdump($data, $filename='test', $append=false){
        $root=dirname(dirname(dirname(__DIR__)));
        $fileName =  rtrim($root,'/') . '/api/log/' . date('Ymd') . '/' . $filename . '_fdump.php';
        $dirName = dirname($fileName);
        if(!file_exists($dirName)){
            mkdir($dirName, 0777, true);
        }
        $debug_trace = debug_backtrace();
        $file = __FILE__ ;
        $line = "unknown";
        if (isset($debug_trace[0]) && isset($debug_trace[0]['file'])) {
            $file = $debug_trace[0]['file'] ;
            $line = $debug_trace[0]['line'];
        }
        $f_l = '['.$file.' : '.$line.']';

        if($append){
            if(!file_exists($fileName)){
                file_put_contents($fileName,'<?php');
            }
            if(file_exists($fileName)){
                $upLevelFile=dirname($fileName);
                chmod($upLevelFile,0777);
                $upLevelFile=dirname($upLevelFile);
                chmod($upLevelFile,0777);
            }
            file_put_contents($fileName,PHP_EOL.$f_l.PHP_EOL.date('Y-m-d H:i:s').' '.PHP_EOL.var_export($data,true).PHP_EOL,FILE_APPEND);
        }
        else{
            file_put_contents($fileName,'<?php'.PHP_EOL.date('Y-m-d H:i:s').' '.PHP_EOL.var_export($data,true));
        }
    }

}