<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      物业订单相关常量
 */

namespace app\consts\order;

class PropertyOrderConst
{
    /** @var string redis tag */
    const PROPERTY_ORDER_REDIS_TAG = 'PropertyOrderTag';
    /** @var string 接口 redis cacheKey */
    const PROPERTY_ORDER_REQUEST_REDIS_KEY = 'PropertyOrder:request:';
    /** @var integer 接口 缓存时长 单位秒 */
    const PROPERTY_ORDER_REQUEST_REDIS_TIMES = 7200;
    /** @var integer 接口 长时间 缓存时长 单位秒 */
    const PROPERTY_ORDER_REQUEST_LONG_REDIS_TIMES = 259200;
}