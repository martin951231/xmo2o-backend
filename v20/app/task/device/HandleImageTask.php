<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      图片处理队列
 */

namespace app\task\device;

use app\community\model\service\Device\HikGateWayEventService;
use yunwuxin\cron\Task;

class HandleImageTask extends Task
{
    public function configure ()
    {
        // 5分钟执行一次
        $this->everyFiveMinutes();
    }

    public function execute ()
    {
        try {
            (new HikGateWayEventService())->TaskHandleImage();
        }catch (\Exception $e){
            fdump_api(['执行错误'=>$e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()],'task/errHandleImageTask');
        }catch (\Throwable $e){
            fdump_api(['执行错误'=>$e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()],'task/errHandleImageTask');
        }catch (\RuntimeException $e){
            fdump_api(['执行错误'=>$e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()],'task/errHandleImageTask');
        }catch (\InvalidArgumentException $e){
            fdump_api(['执行错误'=>$e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()],'task/errHandleImageTask');
        }
    }
}