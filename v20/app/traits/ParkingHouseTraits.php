<?php
/**
 * @author : 合肥快鲸科技有限公司
 * @date : 2022/11/17
 */

namespace app\traits;
use app\job\ParkingHouseJob;
use think\facade\Queue;

trait ParkingHouseTraits
{


    /**
     * 队列名称
     * @author : lkz
     * @date : 2022/11/17
     * @return string
     */
    public function parkingHouseQueueName()
    {
        return 'smart-parking-house';
    }


    /**
     * 立即下发队列
     * @author : lkz
     * @date : 2022/11/17
     * @param $queueData
     * @return mixed
     */
    protected function parkingHouseQueuePushToJob($queueData)
    {
        return  Queue::push(ParkingHouseJob::class,$queueData,$this->parkingHouseQueueName());
    }


    //临时打印日志
    public function parkingHouseFdump($data, $filename='test', $append=false){
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