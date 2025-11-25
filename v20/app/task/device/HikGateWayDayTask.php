<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      海康网关设备 每天执行
 */

namespace app\task\device;

use app\community\model\service\Device\HouseDeviceQrcodeService;
use yunwuxin\cron\Task;

class HikGateWayDayTask extends Task
{
    public function configure()
    {
        $this->dailyAt('00:11');   // 指定时间执行 每天凌晨11分执行
    }

    public function execute()
    {
        try {
            (new HouseDeviceQrcodeService())->over_time_delete_device_qrcode();
            (new HouseDeviceQrcodeService())->over_visitor_delete();
        }catch (\Exception $e){
            fdump_api(['执行错误'=>$e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()],'task/errHikGateWayDayTaskLog',true);
        }catch (\Throwable $e){
            fdump_api(['执行错误'=>$e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()],'task/errHikGateWayDayTaskLog',true);
        }catch (\RuntimeException $e){
            fdump_api(['执行错误'=>$e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()],'task/errHikGateWayDayTaskLog',true);
        }catch (\InvalidArgumentException $e){
            fdump_api(['执行错误'=>$e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()],'task/errHikGateWayDayTaskLog',true);
        }
    }
}