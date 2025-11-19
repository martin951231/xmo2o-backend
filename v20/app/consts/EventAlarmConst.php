<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      报警事件订阅消息-常量
 */

namespace app\consts;

class EventAlarmConst
{
    /**
     * @var int 海康云眸监控
     */
    const HIK_NEI_BU_EVENT_ALARM = 1;

    /**
     * @var int 大华云睿监控
     */
    const DH_YUN_RUI_EVENT_ALARM = 2;

    /**
     * @var int Hik Device Gateway 海康网关-监控
     */
    const HIK_DEVICE_GATEWAY_EVENT_ALARM = 3;
}