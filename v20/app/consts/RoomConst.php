<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      小区房屋相关
 */

namespace app\consts;

class RoomConst
{
    /**
     * @var int 默认 usernum最初的规则 
     */
    const ROOM_USERNUM_BY_DEFAULT = 0;

    /**
     * @var int usernum 生成源于 小区ID+楼栋ID+单元ID+楼层ID+房号ID
     */
    const ROOM_USERNUM_BY_AUTO_BELONG_ID = 1;

    /**
     * @var int usernum 生成源于 导入房间时候用户填写的物业编号
     */
    const ROOM_USERNUM_BY_IMPORT_USER = 2;

    /**
     * @var int usernum 生成源于 楼栋编号+单元编号+楼层编号+房间编号
     */
    const ROOM_USERNUM_BY_AUTO_BELONG_NUMBER = 3;

    /**
     * @var int usernum 生成源于 楼栋编号+单元编号+房间编号
     */
    const ROOM_USERNUM_BY_AUTO_NUMBER = 4;
}