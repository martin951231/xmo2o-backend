<?php

/**
 * Date: 2023/5/27
 */

namespace app\traits;

use app\job\ParkPassageHouseJob;
use think\facade\Queue;

trait ParkPassageHouseTraits
{

    /**
     * 队列名称
     */
    public function parkPassageHouseQueueName()
    {
        return 'park-passage-house';
    }

    /**
     * 立即下发队列
     */
    protected function parkPassageQueuePushToJob($queueData)
    {
        return  Queue::push(ParkPassageHouseJob::class,$queueData,$this->parkPassageHouseQueueName());
    }

    protected function parkPassageQueuePushLaterToJob($queueData,$time=2)
    {
        try {
            return  Queue::later($time, ParkPassageHouseJob::class, $queueData, $this->parkPassageHouseQueueName());
        } catch (\Exception $e) {
            return '';
        }
    }
    //临时打印日志
    public function parkPassageFdump($data, $filename='test', $append=false){
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
            file_put_contents($fileName,PHP_EOL.$f_l.PHP_EOL.date('Y-m-d H:i:s').' '.PHP_EOL.var_export($data,true).PHP_EOL,FILE_APPEND);
        }
        else{
            file_put_contents($fileName,'<?php'.PHP_EOL.date('Y-m-d H:i:s').' '.PHP_EOL.var_export($data,true));
        }
    }

}