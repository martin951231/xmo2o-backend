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

namespace app\consts;

class Cloud260Const
{
    /** @var string 260云 redis tag */
    const ClOUD_260_JOB_REDIS_TAG = 'Cloud260InteractiveTag';
    /** @var string  260云 下发队列 redis cacheKey */
    const ClOUD_260_TRAITS_REDIS_KEY = 'Cloud260Interactive:traits:';
    /** @var string 260云 队列 redis cacheKey */
    const ClOUD_260_JOB_REDIS_KEY = 'Cloud260Interactive:job:';

    /**
     * 当前设备最新版本.
     */
   const DEVICE_260_CLOUD_VERSION = 'v2.0-20230515';

    /**
     * @var int 设备主动触发云端导入雇员授权信息 单次返回条数
     */
    const CLOUD_260_SELECT_PASS_INFO_SIZE = 30;

    /**
     * @var int 设备主动触发云端获取删除雇员信息 单次返回条数
     */
    const CLOUD_260_SELECT_DELETE_INFO_SIZE = 30;

    /**
     * @var int 指令方向 0-反馈设备
     */
    const CMD_DIRECTION0 = 0;

    /**
     * @var int 指令方向 1-反馈平台
     */
    const CMD_DIRECTION1 = 1;

    /**
     * @var int 人脸设备类型值
     */
    const CLOUD_260_DEVICE_TYPE = 30;

    /**
     * @var int 二维码刷新间隔
     */
    const CLOUD_260_QRCODE_REFRESH_INTERVAL = 7200;
    
    const CLOUD_260_FACE_USER_BIND_DEVICE_BIND_TYPE = 30;

    /**
     * @var int 清除相同指令时间间隔
     */
    const CLOUD_260_CMD_CLEAR_SPACE_TIMES = 120;
}