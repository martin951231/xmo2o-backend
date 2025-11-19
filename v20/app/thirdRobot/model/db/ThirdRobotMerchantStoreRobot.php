<?php

namespace app\thirdRobot\model\db;

use think\Model;

class ThirdRobotMerchantStoreRobot extends Model
{
   const IS_DEL_0 = 0;
   const IS_DEL_1 = 1;
   
   public function mapPoint()
   {
       return $this->belongsTo(ThirdRobotMapPoints::class, 'map_point_id', 'id');
   }
}