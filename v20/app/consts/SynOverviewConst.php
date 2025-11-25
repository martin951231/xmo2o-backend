<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      同步概况常量
 */

namespace app\consts;

class SynOverviewConst
{
    /**
     * @var string 同步监控设备
     */
    const SYN_CAMERA_DEVICE = 'sys_camera_device';
    
    /**
     * @var string 同步人脸设备
     */
    const SYN_FACE_DEVICE = 'sys_face_device';
    
    /**
     * @var string 人脸设备设置监听服务
     */
    const SET_FACE_HTTP_HOSTS = 'set_face_http_hosts';

    /**
     * @var string 人脸设备设置监听服务
     */
    const SET_CAMERA_HTTP_HOSTS = 'set_camera_http_hosts';

    /**
     * @var string 同步的操作对象不存在
     */
    const SYN_NON_EXISTENT = 'sys_non_existent';

    /**
     * @var string 同步的操作缺少相关参数
     */
    const SYN_NON_PARAM = 'sys_non_param';

    /**
     * @var string 同步的操作缺少配置
     */
    const SYN_NON_CONFIG = 'sys_non_config';

    /**
     * @var string 同步错误-接口反馈的
     */
    const SYN_ERR_FROM_DEVICE_CLOUD = 'syn_err_from_device_cloud';

    /**
     * @var string 同步成功
     */
    const SYN_SUCCESS = 'syn_success';
}