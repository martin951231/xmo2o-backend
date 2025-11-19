<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      小区工作人员相关
 */

namespace app\consts;

class HouseWorkConst
{
    //**************  移动管理端角色↓//
    /**
     * @var integer 移动管理端 街道工作人员
     */
    const MANAGE_APP_STREET_WORK = 1;
    /**
     * @var integer 移动管理端 街道管理员-暂未使用
     */
    const MANAGE_APP_STREET_ADMIN = 101;
    /**
     * @var integer 移动管理端 社区管理员-暂未使用
     */
    const MANAGE_APP_COMMUNITY_ADMIN = 201;
    /**
     * @var integer 移动管理端 社区工作人员-暂未使用
     */
    const MANAGE_APP_COMMUNITY_WORK = 202;
    /**
     * @var integer 移动管理端 物业总管理员
     */
    const MANAGE_APP_PROPERTY_ADMIN = 3;
    /**
     * @var integer 移动管理端 物业普通管理员
     */
    const MANAGE_APP_PROPERTY_USER = 4;
    /**
     * @var integer 移动管理端 小区物业管理人员
     */
    const MANAGE_APP_VILLAGE_ADMIN = 5;
    /**
     * @var integer 移动管理端 小区物业工作人员
     */
    const MANAGE_APP_VILLAGE_WORK = 6;
    //**************  注意↑ 移动管理端角色 //
}