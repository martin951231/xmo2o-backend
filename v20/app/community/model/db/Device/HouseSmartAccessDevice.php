<?php

namespace app\community\model\db\Device;

use think\Model;

class HouseSmartAccessDevice extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $name = 'house_smart_access_device';

    protected $pk = 'id';

    public function getColumn($where, $column, $key = '')
    {
        return $this->where($where)->column($column, $key);
    }
}
