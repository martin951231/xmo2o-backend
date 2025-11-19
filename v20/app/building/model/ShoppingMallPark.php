<?php

namespace app\building\model;

use think\Model;
use think\model\concern\SoftDelete;

class ShoppingMallPark extends Model
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
}
