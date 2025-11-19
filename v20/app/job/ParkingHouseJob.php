<?php
/**
 * @author : 合肥快鲸科技有限公司
 * @date : 2022/11/17
 */

namespace app\job;

use app\community\model\service\Park\A11Service;
use think\Exception;
use think\queue\Job as DoJob;


class ParkingHouseJob
{

    //出队列
    public function fire(DoJob $job , $data)
    {
        if ($job->attempts() >= 1){
            $job->delete();
        }
        try {
            (new A11Service())->parkingQueue($data);

        }catch (Exception $e){}
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
    
    
    public function failed($data)
    { 
        $this->parkingHouseFdump(['队列执行失败了--->：'.__FUNCTION__.__LINE__,$data],'a11_park/Ajob_error',1);
    }


}