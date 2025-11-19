<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      机器人常量
 */

namespace app\consts;

class RobotConst
{
    /**
     * 达闼机器人
     */
    const DATA_ROBOT_DEVICE_CMD_TYPE = 'cross-data-robot';

    const DATA_GREETINGS_NAME = '{姓名}';
    const DATA_GENDER_CALL = '{称呼}';

    /**
     * @var int 相同人员呼叫机器人 频率间隔
     */
    const TIME_CALL_SPACE = 3;

    /** @var string 达闼机器人redis tag */
    const ROBOT_JOB_REDIS_TAG          = 'RobotInteractiveTag';
    /** @var string 达闼机器人redis cacheKey */
    const ROBOT_TRAITS_REDIS_KEY       = 'RobotInteractive:traits:';
    /** @var string 达闼机器人redis cacheKey */
    const ROBOT_JOB_REDIS_KEY          = 'RobotInteractive:job:';
}