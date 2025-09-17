<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      kumike（酷米客-杂淘）-A4(咚咚)人脸设备 相关常量
 */

namespace app\consts\device;

class A4DeviceConst
{
    /** @var string A4 redis tag */
    const A4_DEVICE_JOB_REDIS_TAG = 'A4DeviceInteractiveTag';
    /** @var string A4 下发队列 redis cacheKey */
    const A4_DEVICE_TRAITS_REDIS_KEY = 'A4DeviceInteractive:traits:';
    /** @var string A4 队列 redis cacheKey */
    const A4_DEVICE_JOB_REDIS_KEY = 'A4DeviceInteractive:job:';

}