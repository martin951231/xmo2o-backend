<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      设备二维码相关常量
 */

namespace app\consts;

class DeviceQrcodeConst
{
    /**
     * @var string 绑定人员类型 住户人员
     */
    const BIND_TYPE_VILLAGE_USER = 'village_user';
    
    /**
     * @var string 绑定人员表来源 住户表-house_village_user_bind
     */
    const BIND_FROM_HOUSE_VILLAGE_USER_BIND = 'house_village_user_bind';
}