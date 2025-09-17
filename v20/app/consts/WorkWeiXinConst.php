<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      企业微信相关
 */

namespace app\consts;


class WorkWeiXinConst
{
    /**
     * @var string 关联数据 物业
     * 目前用于表 work_weixin_user 中 from
     */
    const FROM_PROPERTY = 'property';
    /**
     * @var string 关联数据 移动管理端企业微信登录
     * 目前用于表 work_weixin_user 中 from
     */
    const FROM_COMMUNITY_MANAGE_WORK_WEI_XIN_LOGIN = 'community_manage_qywx_login';
    
    /** 
     * @var string 读取成员接口获取的信息 https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token=ACCESS_TOKEN&userid=USERID 
     * 目前用于表 work_weixin_user 中 info_type
     */
    const INFO_TYPE_WORK_WEI_XIN_USER_GET          = 'user_get';

    /**
     * @var string 获取访问用户身份 https://qyapi.weixin.qq.com/cgi-bin/service/auth/getuserinfo3rd?suite_access_token=SUITE_ACCESS_TOKEN&code=CODE
     * 目前用于表 work_weixin_user 中 info_type
     */
    const INFO_TYPE_WORK_WEI_XIN_AUTH_USER_INFO    = 'auth_user_info';

    /**
     * @var string 读取成员接口获取的信息 https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token=ACCESS_TOKEN&userid=USERID
     * 目前用于表 work_weixin_user 中 change_type
     */
    const CHANGE_TYPE_WORK_WEI_XIN_GET_USER        = 'get_user';

    /**
     * @var string 获取访问用户身份 https://qyapi.weixin.qq.com/cgi-bin/service/auth/getuserinfo3rd?suite_access_token=SUITE_ACCESS_TOKEN&code=CODE
     * 目前用于表 work_weixin_user 中 change_type
     */
    const CHANGE_TYPE_WORK_WEI_XIN_GET_AUTH_INFO   = 'get_auth_info';

    /** 
     * @var string 关联手机号类型 login 登录
     * 目前用于表 work_weixin_phone 中 type
     */
    const WORK_WEI_XIN_PHONE_LOGIN_TYPE       = 'login';
    /**
     * @var string 记录下关联表 拿表名当类别 house_property 物业信息表
     * 目前用于表 work_weixin_phone 中 from_table
     */
    const WORK_WEI_XIN_PHONE_FROM_PROPERTY    = 'house_property';
    /**
     * @var string 记录下关联表 拿表名当类别 area_street 街道、社列表信息表
     * 目前用于表 work_weixin_phone 中 from_table
     */
    const WORK_WEI_XIN_PHONE_FROM_AREA_STREET = 'area_street';

    /**
     * @var int 企业微信登录移动管理绑定手机号
     * 关键
     */
    const COMMUNITY_MANAGE_LOGIN_BIND_PHONE_CODE_TYPE = 'authQy';
    /** 
     * @var int 企业微信登录移动管理绑定手机号
     * 目前用于表 app_sms_record 中 type
     */
    const COMMUNITY_MANAGE_LOGIN_BIND_PHONE_CODE = 40;
    
    /** @var string 缺少服务商配置项提示 */
    const WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP = '请前往“服务商管理端-应用管理-网页应用-对应创建的普通应用”中复制相关配置进行设置';

    /** @var string 企业微信redis tag */
    const WORK_WEI_XIN_JOB_REDIS_TAG          = 'WorkWeiXinInteractiveTag';
    /** @var string 企业微信redis cacheKey */
    const WORK_WEI_XIN_TRAITS_REDIS_KEY       = 'WorkWeiXinInteractive:traits:';
    /** @var string 企业微信redis cacheKey */
    const WORK_WEI_XIN_JOB_REDIS_KEY          = 'WorkWeiXinInteractive:job:';
    /** @var string 平台用户转企业微信userid  redis cacheKey */
    const WORK_WEI_XIN_USER_TO_WORK           = 'WorkWeiXinInteractive:userToWork:';
}