<?php

namespace app\community\model\db\Device;

use think\Model;

class HouseNonMotorVehicle extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $name = 'house_non_motor_vehicle';

    protected $pk = 'id';
}