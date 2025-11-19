<?php

namespace app\building\model;

use think\facade\Db;
use think\Model;

class ShoppingBuildingOrder extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    const STATUS_WAIT = 0;

    const STATUS_PAID = 10;

    const STATUS_CANCEL = 20;
}
