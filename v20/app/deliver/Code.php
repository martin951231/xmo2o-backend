<?php

namespace app\deliver;

/**
 * 配送员错误状态吗
 *
 * @author: 张涛
 * @date: 2020/09/25
 */
class Code
{

    /***************************配送员业务状态码***************************/

    //配送员已抢单
    const ORDER_ACCEPTED = 30001;

    //转单记录不存在
    const TRANSFER_ORDER_NOT_EXIST = 30002;

    //转单过期
    const TRANSFER_ORDER_EXPIRED = 30003;

    //超出最大接单量
    const OVER_MAX_NUM = 30004;

    //骑手多设备登录退出
    const MULTI_DEVICE_LOGIN = 90001;
}
