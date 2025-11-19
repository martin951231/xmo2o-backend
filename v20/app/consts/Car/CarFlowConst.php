<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      大华相关常量
 */

namespace app\consts\Car;

class CarFlowConst
{
    /**
     * @var int 队列打印是否为追加打印.
     */
    const CAR_FLOW_TRAITS_LOG_APPEND = 1;

    /**
     * @var int 显屏有效时长当前默认 3分钟 180秒.
     */
    const CAR_FLOW_PARK_SHOW_SCREEN_LIMIT_TIME = 180;

    /**
     * @var int 开门指令有效时间 3分钟 180秒.
     */
    const CAR_FLOW_OPEN_LOG_LIMIT_TIME = 180;

    /**
     * @var int 车辆农信银行支付有效时间 16分钟 960秒.
     */
    const CAR_FLOW_SCRCU_RECORD_LIMIT_TIME = 960;
    
    /**
     * @var int 智能停车485串口数据上报表有效时间 10分钟 600秒.
     */
    const PARK_SERIAL_LOG_LIMIT_TIME = 600;
    
    /**
     * @var int 停车相关缓存默认时长（秒）.
     */
    const CAR_FLOW_REDIS_EXPIRE = 7200;
}