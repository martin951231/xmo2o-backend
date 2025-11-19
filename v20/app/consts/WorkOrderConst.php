<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      新版工单相关
 */

namespace app\consts;

class WorkOrderConst
{
    /**
     * @var int 提交工单的时候最多上传图片张数.
     */
    const WORK_ORDER_UPLOAD_IMAGE_MAX = 8;

    /**
     * @var int 提交工单的时候最多上传视频份数.
     */
    const WORK_ORDER_UPLOAD_VIDEO_MAX = 4;

    /**
     * @var int 签退和签到直减最少的时间间隔.
     */
    const WORK_ORDER_SIGN_TIMES = 60;

    /**
     * @var int 打卡签到标识.
     */
    const WORK_ORDER_SIGN_TYPE_IN = 'signIn';

    /**
     * @var int 打卡签退标识.
     */
    const WORK_ORDER_SIGN_TYPE_OUT = 'signOut';



    /**
     * @var int 缺少楼栋管家.
     */
    const WORK_ORDER_ERR_CODE_NOT_BUILDER = 4001;

    /**
     * @var string 用户同意工单方案后自动结单.
     */
    const WORK_ORDER_AUTO_END = 'work_order_auto_end';

    /**
     * @var string 未接单进行通知.
     */
    const WORK_ORDER_NOT_GRAB_NOTICE = 'work_order_not_grab_notice';

    /**
     * @var string 未接单进行上报.
     */
    const WORK_ORDER_NOT_GRAB_REPORT = 'work_order_not_grab_report';

    /**
     * @var string 超时未处理上报总后台.
     */
    const WORK_ORDER_OVER_NOT_PROCESSED_REPORT = 'over_not_processed_report';

    /**
     * @var string 收费工单确认超时处理.
     */
    const WORK_ORDER_OVER_FEE_CONFIRM_OPERATE = 'over_fee_confirm_operate';
    

    /** @var string 工单分类配置redis tag */
    const WORK_ORDER_CATE_CONFIG_REDIS_TAG = 'WorkOrderCateConfigTag';
}