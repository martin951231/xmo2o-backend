<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      三方导入数据常量
 */

namespace app\consts;

class newChargeConst
{
    /*************  计费模式 1:固定费用 2：单价计量单位 3 临时车  4 月租车  5 车位数量  ****************/
    /** @var int 固定费用 */
    const FEES_TYPE_FIXED_EXPENSES           = 1;
    /** @var int 单价计量单位 */                   
    const FEES_TYPE_UNIT_PRICE_UOM           = 2;
    /** @var int 临时车 */                      
    const FEES_TYPE_TEMPORARY_VEHICLE        = 3;
    /** @var int 月租车 */                      
    const FEES_TYPE_MONTHLY_CAR_RENTAL       = 4;
    /** @var int 车位数量 */
    const FEES_TYPE_NUMBER_OF_PARKING_SPACES = 5;
}