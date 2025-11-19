<?php

namespace app\mall\model\db;

use think\Model;

class MallOrderDelivery extends Model
{
    const IS_DEL_0 = 0;//未删除
    const IS_DEL_1 = 1;//已删除
}