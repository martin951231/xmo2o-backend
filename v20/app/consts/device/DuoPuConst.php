<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      朵普相关常量
 */

namespace app\consts\device;

class DuoPuConst
{
    /**
     * @var int 人脸设备类型值-朵普
     */
    const DUO_PU_DEVICE_TYPE = 29;
    
    /** @var string 朵普 redis tag */
    const DUO_PU_JOB_REDIS_TAG = 'DuoPuInteractiveTag';
    /** @var string 朵普 下发队列 redis cacheKey */
    const DUO_PU_TRAITS_REDIS_KEY = 'DuoPuInteractive:traits:';
    /** @var string 朵普 redis cacheKey */
    const DUO_PU_JOB_REDIS_KEY = 'DuoPuInteractive:job:';
}