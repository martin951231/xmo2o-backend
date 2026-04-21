<?php

namespace app\community\model\db\Device;

use think\Model;

class HouseSmartAccessRecords extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $name = 'house_smart_access_records';

    protected $pk = 'id';
}
